<?php

namespace EnsphereCore\Commands\Ensphere\Back\Resource;

use Illuminate\Console\Command as IlluminateCommand;
use EnsphereCore\Commands\Ensphere\Traits\Module;

class Command extends IlluminateCommand
{

    use Module;

    /**
     * The singular name of the resource
     *
     * @var string
     */
    protected $signature = 'ensphere:back-resource {resource}';

    /**
     * @var string
     */
    protected $description = 'Creates a Controller, Blueprint, Contract, Model and Routes stub for your back end application resource; artisan ensphere:resource "product group"';

    /**
     * @var
     */
    protected $singular;

    /**
     * @var
     */
    protected $plural;

    /**
     * @var
     */
    protected $time;

    /**
     * Handle the command... #obs
     */
    public function handle()
    {
        $this->singular = $this->argument( 'resource' );
        $this->plural = str_plural( $this->singular );
        $this->time = time();
        $this->createModel();
        $this->createBlueprint();
        $this->createContract();
        $this->createController();
        $this->createRoutesStub();
        $this->createViews();
        $this->createPermissionMigrations();
        $this->registerBindings();
        $this->runExport();
        $this->runImport();
        $this->runExport();
    }

    private function registerBindings()
    {
        $path = base_path( 'EnsphereCore/ensphere-registration.json' );
        $json = json_decode( file_get_contents( $path ) );
        $json->contracts->{$this->getEscapedBlueprintPath()} = $this->getEscapedContractPath();

        file_put_contents( $path, json_encode( $json, JSON_PRETTY_PRINT ) );
    }

    private function runExport()
    {
        $this->call( 'ensphere:export' );
    }

    private function runImport()
    {
        $this->call( 'ensphere:import' );
    }

    /**
     * @return void
     */
    private function createPermissionMigrations()
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

    /**
     * @param $folder
     * @return mixed
     */
    private function folder( $folder )
    {
        if( ! file_exists( $folder ) ) {
            mkdir( $folder, 0755 );
        }
        return $folder;
    }

    private function createViews()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        $moduleViews = resource_path( 'views/' . $appData[ 'module' ] );
        if( ! file_exists( $moduleViews ) ) {
            mkdir( $moduleViews, 0755 );
        }
        $folder = resource_path( 'views/' . $appData[ 'module' ] . '/' . str_slug( $this->singular ) );
        if( ! file_exists( $folder ) ) {
            mkdir( $folder, 0755 );
        }

        $views = [
            'index.blade.php' => __DIR__ . '/stubs/views/index.stub',
            'edit.blade.php' => __DIR__ . '/stubs/views/edit.stub',
            'create.blade.php' => __DIR__ . '/stubs/views/create.stub',
        ];

        foreach( $views as $blade => $path ) {

            $contents = $this->replace( file_get_contents( $path ) );

            $filePath = $folder . '/' . $blade;
            if( ! file_exists( $folder ) ) {
                $this->warn( "{$folder} does not exist, skipping..." ); continue;
            }
            if( file_exists( $filePath ) ) {
                $this->warn( "{$filePath} already exists, skipping..." ); continue;
            }
            file_put_contents( $filePath, $contents );
            $this->info( "{$filePath} created..." );
        }
    }

    /**
     * Create Routes Stub
     * @return void
     */
    private function createRoutesStub()
    {
        if( ! file_exists( app_path( 'Routes' ) ) ) {
            mkdir( app_path( 'Routes' ), 0755 );
        }
        if( $this->create( 'routes.stub', 'Routes', $this->getRoutesName() ) ) {
            $this->info( 'add ' . $this->getNamespace() . '\\Routes\\' . $this->getRoutesName() . '::routes( $router ); in your routes contract.' );
        }
    }

    /**
     * Create Controller
     * @return void
     */
    private function createController()
    {
        $this->create( 'controller.stub', 'Http/Controllers', $this->getControllerName() );
    }

    /**
     * Create Contract
     * @return void
     */
    private function createContract()
    {
        $this->create( 'contract.stub', 'Contracts', $this->getContractName() );
    }

    /**
     * Create Blueprint
     * @return void
     */
    private function createBlueprint()
    {
        $this->create( 'blueprint.stub', 'Contracts/Blueprints', $this->getBlueprintName() );
    }

    /**
     * Create Model
     * @return void
     */
    private function createModel()
    {
        $this->create( 'model.stub', 'Models', $this->getModelName() );
    }

    /**
     * @param $stubPath
     * @param $folder
     * @param $className
     * @return bool
     */
    public function create( $stubPath, $folder, $className )
    {
        $contents = $this->replace( file_get_contents( __DIR__ . '/stubs/' . $stubPath ) );
        $folder = app_path( $folder );
        $filePath = $folder . '/' . $className . '.php';
        if( ! file_exists( $folder ) ) {
            $this->warn( "{$folder} does not exist, skipping..." ); return false;
        }
        if( file_exists( $filePath ) ) {
            $this->warn( "{$filePath} already exists, skipping..." ); return false;
        }
        file_put_contents( $filePath, $contents );
        $this->info( "{$filePath} created..." );
        return true;
    }

    /**
     * @param $contents
     * @return mixed
     */
    private function replace( $contents )
    {
        return str_replace(
          [
              '{%NAMESPACE%}',
              '{%TABLE_NAME%}',
              '{%URL_ROUTE%}',
              '{%BLUEPRINT_NAMESPACE%}',
              '{%BLUEPRINT_NAME%}',
              '{%MODEL_NAMESPACE%}',
              '{%MODEL_NAME%}',
              '{%CONTRACT_NAME%}',
              '{%PLURAL_VARIABLE%}',
              '{%SINGULAR_VARIABLE%}',
              '{%VIEW_PATH%}',
              '{%ID_VARIABLE%}',
              '{%CONTROLLER_NAME%}',
              '{%ROUTE_NAME_PREFIX%}',
              '{%ROUTE_PREFIX%}',
              '{%ROUTE_CLASS_NAME%}',
              '{%VIEW_TITLE%}',
              '{%PLURAL_HEADING%}',
              '{%SINGULAR_HEADING%}',
              '{%MODULE_HEADING%}',
              '{%DATE_ID%}',
              '{%DATE_FILE%}'
          ],
          [
              $this->getNamespace(),
              $this->getModelTableName(),
              $this->getUrlRoute(),
              $this->getBlueprintNamespace(),
              $this->getBlueprintName(),
              $this->getModelNamespace(),
              $this->getModelName(),
              $this->getContractName(),
              $this->getPluralVariable(),
              $this->getSingularVariable(),
              $this->getViewPath(),
              $this->getIdVariable(),
              $this->getControllerName(),
              $this->getRouteNamePrefix(),
              $this->getRoutePrefix(),
              $this->getRoutesName(),
              $this->getViewTitle(),
              $this->getPluralHeading(),
              $this->getSingularHeading(),
              $this->getModuleHeading(),
              $this->getDateId(),
              $this->getDateForFileName(),
          ],
          $contents
        );
    }

    /**
     * @return false|string
     */
    private function getDateId()
    {
        return date( "YmdHis", $this->time );
    }

    /**
     * @return false|string
     */
    private function getDateForFileName()
    {
        return date( "Y_m_d_His", $this->time );
    }

    /**
     * @return string
     */
    private function getEscapedBlueprintPath()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return $appData['camelCasedVendor'] . '\\' . $appData['camelCasedModule'] . '\\Contracts\\Blueprints\\' . $this->getBlueprintName();
    }

    /**
     * @return string
     */
    private function getEscapedContractPath()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return $appData['camelCasedVendor'] . '\\' . $appData['camelCasedModule'] . '\\Contracts\\' . $this->getContractName();
    }

    /**
     * @return string
     */
    private function getModuleHeading()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return ucwords( str_replace( [ 'admin ', 'front ' ], '', str_replace( '-', ' ', $appData[ 'module' ] ) ) );
    }

    /**
     * @return string
     */
    private function getPluralHeading()
    {
        return ucwords( $this->plural );
    }

    /**
     * @return string
     */
    private function getSingularHeading()
    {
        return ucwords( $this->singular );
    }

    /**
     * @return string
     */
    private function getViewTitle()
    {
        return ucwords( $this->plural ) . ' <small>Create and manage your ' . $this->plural . '</small>';
    }

    /**
     * @return string
     */
    private function getNamespace()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return $appData['camelCasedVendor'] . "\\" . $appData['camelCasedModule'];
    }

    /**
     * @return string
     */
    private function getModelTableName()
    {
        return str_slug( $this->plural, '_' );
    }

    /**
     * @return string
     */
    private function getRoutePrefix()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return 'admin/' . str_replace( 'admin-', '', $appData[ 'module' ] ) . '/' . str_slug( $this->plural );
    }

    /**
     * @return string
     */
    private function getUrlRoute()
    {
        return 'get.' . $this->getViewPath() . '.edit';
    }

    /**
     * @return string
     */
    private function getRouteNamePrefix()
    {
        return $this->getViewPath();
    }

    /**
     * @return string
     */
    private function getBlueprintNamespace()
    {
        return $this->getNamespace() . '\\Contracts\\Blueprints';
    }

    /**
     * @return string
     */
    private function getBlueprintName()
    {
        return $this->getContractName() . 'Blueprint';
    }

    /**
     * @return string
     */
    private function getRoutesName()
    {
        return $this->getContractName() . 'Routes';
    }

    /**
     * @return string
     */
    private function getModelNamespace()
    {
        return $this->getNamespace() . '\\Models';
    }

    /**
     * @return string
     */
    private function getModelName()
    {
        return ucfirst( camel_case( $this->singular ) );
    }

    /**
     * @return string
     */
    private function getContractName()
    {
        return ucfirst( camel_case( $this->plural ) );
    }

    /**
     * @return string
     */
    private function getPluralVariable()
    {
        return camel_case( $this->plural );
    }

    /**
     * @return string
     */
    private function getSingularVariable()
    {
        return camel_case( $this->singular );
    }

    /**
     * @return string
     */
    private function getViewPath()
    {
        $appData = $this->getCurrentVendorAndModuleName();
        return $appData[ 'module' ] . '.' . str_slug( $this->singular );
    }

    /**
     * @return string
     */
    private function getIdVariable()
    {
        return $this->getSingularVariable() . 'Id';
    }

    /**
     * @return string
     */
    private function getControllerName()
    {
        return $this->getContractName() . 'Controller';
    }


}
