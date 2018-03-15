<?php

namespace EnsphereCore\Commands\Ensphere\SearchAndReplace;

use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Facades\DB;

class Command extends IlluminateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:search-and-replace';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
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
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        $containing = [];
        foreach( $tables as $tableName ) {
            $columnNames = DB::getSchemaBuilder()->getColumnListing( $tableName );
            foreach( $columnNames as $columnName ) {
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
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        $queries = [];
        foreach( $tables as $tableName ) {
            $columnNames = DB::getSchemaBuilder()->getColumnListing( $tableName );
            foreach( $columnNames as $columnName ) {
                $type = DB::connection()->getDoctrineColumn( $tableName, $columnName )->getType()->getName();
                if( in_array( $type, [ 'string', 'text' ] ) ) {
                    $queries[] = "select * from `{$tableName}` where `{$columnName}` like BINARY '%{$search}%'";
                }
            }
        }
        $found = [];
        foreach( $queries as $query ) {
            $results = DB::select( DB::raw( $query ) );
            if( ! empty( $results ) ) {
                $found = array_merge( $found, $results );
            }
        }
        $total = count($found);
        $this->info( "There were {$total} occurrences found." );
    }
}
