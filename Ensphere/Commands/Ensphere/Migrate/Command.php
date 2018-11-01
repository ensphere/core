<?php

namespace EnsphereCore\Commands\Ensphere\Migrate;

use EnsphereCore\Commands\Ensphere\Traits\Module as ModuleTrait;
use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Database\QueryException;

class Command extends IlluminateCommand
{

    use ModuleTrait;

    /**
     * @var string
     */
    protected $name = 'ensphere:migrate';

    /**
     * @var string
     */
    protected $description = 'Clears up the module to align with installation.';

    /**
     * @var
     */
    private $currentStructure;

    /**
     * @var array
     */
    private $moduleMigrationFiles = [];

    /**
     * @return void
     */
    public function fire()
    {
        event( 'ensphere.migrate.before', [ [ 'console' => $this ] ] );
        $this->currentStructure = $this->getCurrentVendorAndModuleName();
        switch( $this->argument('migration_command') ) {
            case 'run' :
                $this->runMigration();
                break;
            case 'create' :
                $this->createMigration();
                break;
        }
        event( 'ensphere.migrate.after', [ [ 'console' => $this ] ] );
    }

    /**
     * @param $vendorFolder
     * @param $moduleFolder
     */
    protected function checkFoldersExists( $vendorFolder, $moduleFolder )
    {
        $path = base_path( 'database/migrations/vendor/' );
        if( ! file_exists( $path ) ) mkdir( $path, 0777 );
        $path = base_path( 'database/migrations/vendor/' . $vendorFolder . '/' );
        if( ! file_exists( $path ) ) mkdir( $path, 0777 );
        $path = base_path( 'database/migrations/vendor/' . $vendorFolder . '/' . $moduleFolder . '/' );
        if( ! file_exists( $path ) ) mkdir( $path, 0777 );
    }

    /**
     * @return void
     */
    private function createMigration()
    {
        $migrationName = $this->option('name');
        $this->checkFoldersExists( $this->currentStructure['vendor'], $this->currentStructure['module'] );
        Artisan::call( 'make:migration', array(
            'name' => $migrationName,
            '--path' => "database/migrations/vendor/" . $this->currentStructure['vendor'] . "/" . $this->currentStructure['module']
        ));
        $this->info( "migration file created in: database/migrations/vendor/" . $this->currentStructure['vendor'] . "/" . $this->currentStructure['module'] . "/" );
    }

    /**
     * @param $path
     * @param bool $cacheResponse
     * @return array
     */
    protected function getAllModuleMigrationFiles( $path, $cacheResponse = false )
    {
        if( ! empty( $this->moduleMigrationFiles ) ) {
            return $this->moduleMigrationFiles;
        }
        $rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
        $files = [];
        foreach( $rii as $file ) {
            if( ! $file->isDir() && $file->getExtension() === 'php' ){
                if( ! isset( $files[$file->getPath()] ) ) {
                    $files[$file->getPath()] = [];
                }
                $files[$file->getPath()][] = $file->getBasename();
            }
        }
        if( $cacheResponse ) {
            $this->moduleMigrationFiles = $files;
        }
        return $files;
    }

    /**
     * @param $migrations
     */
    protected function addToMigrationsFolder( $migrations )
    {
        foreach( $migrations as $path => $files ) {
            foreach( $files as $fileName ) {
                copy( "{$path}/{$fileName}", base_path( "database/migrations/{$fileName}" ) );
            }
        }
    }

    /**
     * @param $migrations
     */
    protected function returnMigrations( $migrations )
    {
        foreach( $migrations as $path => $files ) {
            foreach( $files as $fileName ) {
                copy( base_path( "database/migrations/{$fileName}" ), "{$path}/{$fileName}" );
                unlink( base_path( "database/migrations/{$fileName}" ) );
            }
        }
    }


    /**
     * @return void
     */
    public function runMigration()
    {
        try {
            $migrations = $this->getAllModuleMigrationFiles( base_path( 'database/migrations/vendor' ), true );
            $this->addToMigrationsFolder( $migrations );
            $this->line( 'running application migration' );
            Artisan::call( 'migrate', [ '--force' => true ] );
            $this->returnMigrations( $migrations );
            $this->seed();
        } catch( QueryException $e ) {
            $this->error( $e->getMessage() );
        }
    }

    /**
     * @return void
     */
    protected function seed()
    {
        $seeds = (array) Session::get( 'seed' );
        foreach( $seeds as $seed ) {
            $this->info( "seeding {$seed}...");
            (new $seed)->run();
        }
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['migration_command', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['name', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

}
