<?php

namespace EnsphereCore\Commands\Ensphere\Back\MigrationSeed;

use EnsphereCore\Commands\Ensphere\Back\Resource\Command as ResourceCommand;

class Command extends ResourceCommand
{

    /**
     * @var string
     */
    protected $signature = 'make:migration-seed';

    /**
     * @var string
     */
    protected $description = 'Creates Migration Seed Stubs';

    /**
     *
     */
    public function handle()
    {
        $this->time = time();
        $this->createPermissionMigrations();
    }

    /**
     * @return void
     */
    protected function createPermissionMigrations()
    {
        $seedFolder = $this->folder( app_path( 'MigrationSeeds' ) );
        $migrationFolder = $this->folder( resource_path( 'database/migrations' ) );
        $contents = $this->replace( file_get_contents( __DIR__ . '/stubs/seed.stub' ) );
        $filePath = $seedFolder . '/SeedPermissions' . date( "YmdHis", $this->time ) . '.php';
        if( file_exists( $filePath ) ) {
            $this->warn( "{$filePath} exists, skipping..." );
        } else {
            file_put_contents( $filePath, $contents );
            $this->info( "{$filePath} created..." );
        }
        $contents = $this->replace( file_get_contents( __DIR__ . '/stubs/migration.stub' ) );
        $filePath = $migrationFolder . '/' . date( "Y_m_d_His", $this->time ) . '_permission_migration_' . date( "YmdHis", $this->time ) . '.php';
        if( file_exists( $filePath ) ) {
            $this->warn( "{$filePath} exists, skipping..." );
        } else {
            file_put_contents( $filePath, $contents );
            $this->info( "{$filePath} created..." );
        }
        file_put_contents( $filePath, $contents );
    }

}
