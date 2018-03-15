<?php

namespace EnsphereCore\Commands\Ensphere\SearchAndReplace;

use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Schema\SchemaException;

class Command extends IlluminateCommand
{

    /**
     * @var array
     */
    protected $onlyTables = [];

    /**
     * @var array
     */
    protected $onlyColumns = [];

    /**
     * @var array
     */
    protected $excludeTables = [];

    /**
     * @var array
     */
    protected $excludeColumns = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:search-and-replace 
        {--only-tables= : Comma separated list of tables to use } 
        {--only-columns= : Comma separated list of columns to use} 
        {--exclude-tables= : Comma separated list of tables to exclude} 
        {--exclude-columns= : Comma separated list of columns to exclude}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search and replace from text and string columns';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function setArguments()
    {
        $this->onlyTables = array_filter( array_map( 'trim', explode( ',', (string) $this->option( 'only-tables' ) ) ) );
        $this->onlyColumns = array_filter( array_map( 'trim', explode( ',', (string) $this->option( 'only-columns' ) ) ) );
        $this->excludeTables = array_filter( array_map( 'trim', explode( ',', (string) $this->option( 'exclude-tables' ) ) ) );
        $this->excludeColumns = array_filter( array_map( 'trim', explode( ',', (string) $this->option( 'exclude-columns' ) ) ) );
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [
            'only-tables' => $this->onlyTables,
            'only-columns' => $this->onlyColumns,
            'exclude-tables' => $this->excludeTables,
            'exclude-columns' => $this->excludeColumns,
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setArguments();
        $search = $this->ask('What string would you like to search for?');
        $replace = $this->choice('Do you want to carry out a replacement or just a search?', [ 'Search only', 'Search and replace' ] );
        $replacement = false;
        if( $replace === 'Search and replace' ) {
            $replacement = $this->ask( "What would you like to replace '{$search}' with?" );
        }
        if( $replacement ) {
            $this->searchAndReplace( $search, $replacement );
        } else {
            $this->searchOccurrences( $search );
        }
    }

    /**
     * @param $search
     * @param $replace
     * @return mixed
     */
    private function searchAndReplace( $search, $replace )
    {
        $this->info( "Searching for columns..." );
        $tables = $this->filterTableNames( DB::connection()->getDoctrineSchemaManager()->listTableNames() );
        $containing = [];
        foreach( $tables as $tableName ) {
            $columnNames = $this->filterColumnNames( DB::getSchemaBuilder()->getColumnListing( $tableName ) );
            foreach( $columnNames as $columnName ) {
                try {
                    $type = DB::connection()->getDoctrineColumn( $tableName, $columnName )->getType()->getName();
                    if( in_array( $type, [ 'string', 'text' ] ) ) {
                        $results = DB::select( DB::raw( "select * from `{$tableName}` where `{$columnName}` like BINARY '%{$search}%'" ) );
                        if( ! empty( $results ) ) {
                            if( isset( $containing[ $tableName ] ) ) {
                                $containing[ $tableName ] = [];
                            }
                            $containing[ $tableName ][] = $columnName;
                        }
                    }
                } catch( SchemaException $e ) {
                    $this->error( $e->getMessage() );
                }
            }
        }
        if( empty( $containing ) ) {
            return $this->info( "0 occurrences found, aborting..." );
        }
        foreach( $containing as $tableName => $columns ) {
            $this->info( "Switching table lookup to `{$tableName}`..." );
            foreach( $columns as $columnName ) {
                $this->info( "Finding `{$search}` in table `{$tableName}` in column `{$columnName}` and replacing with `{$replace}`..." );
                DB::statement( "update `{$tableName}` set `{$columnName}` = replace( `{$columnName}`, '{$search}', '{$replace}' )" );
            }
        }
        $this->info( "Complete!" );
    }

    /**
     * @param $search
     */
    private function searchOccurrences( $search )
    {
        $this->info("Searching for `{$search}` now across every string and text field from every table...");
        $tables = $this->filterTableNames( DB::connection()->getDoctrineSchemaManager()->listTableNames() );
        $queries = [];
        foreach( $tables as $tableName ) {
            $columnNames = $this->filterColumnNames( DB::getSchemaBuilder()->getColumnListing( $tableName ) );
            foreach( $columnNames as $columnName ) {
                try {
                    $type = DB::connection()->getDoctrineColumn( $tableName, $columnName )->getType()->getName();
                    if( in_array( $type, [ 'string', 'text' ] ) ) {
                        $queries[] = [
                            "query" => "select * from `{$tableName}` where `{$columnName}` like BINARY '%{$search}%'",
                            "table" => $tableName,
                            "column" => $columnName
                        ];
                    }
                } catch( SchemaException $e ) {
                    $this->error( $e->getMessage() );
                }
            }
        }
        $totalFound = 0;
        $found = [];
        foreach( $queries as $queryBlock ) {
            $results = DB::select( DB::raw( $queryBlock['query'] ) );
            if( ! empty( $results ) ) {
                if( ! isset( $found[ $queryBlock[ 'table' ] ] ) ) {
                    $found[ $queryBlock[ 'table' ] ] = [];
                }
                $found[ $queryBlock[ 'table' ] ][ $queryBlock[ 'column' ] ] = count( $results );
                $totalFound += count( $results );
            }
        }
        $this->info( "There were {$totalFound} rows found;" );
        foreach( $found as $tableName => $columns ) {
            $this->line( "Table <fg=blue;>{$tableName}</>..." );
            foreach( $columns as $columnName => $count ) {
                $this->line( "\t<fg=blue;>{$columnName}:</> <fg=yellow;>{$count} row(s)</>");
            }
        }
    }

    /**
     * @param $names
     * @return array|mixed
     */
    protected function filterColumnNames( $names )
    {
        $exclude = $this->getArguments()[ 'exclude-columns' ];
        $only = $this->getArguments()[ 'only-columns' ];
        if( ! empty( $only ) ) {
            return $only;
        }
        return array_diff( $names, $exclude );
    }

    /**
     * @param $names
     * @return array|mixed
     */
    protected function filterTableNames( $names )
    {
        $exclude = $this->getArguments()[ 'exclude-tables' ];
        $only = $this->getArguments()[ 'only-tables' ];
        if( ! empty( $only ) ) {
            return $only;
        }
        return array_diff( $names, $exclude );
    }

}
