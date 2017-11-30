<?php

namespace EnsphereCore;

use EnsphereCore\Commands\Ensphere\InformCentralHub;
use EnsphereCore\Commands\Ensphere\Process\PostProcessCommand;
use EnsphereCore\Commands\Ensphere\Process\PreProcessCommand;
use EnsphereCore\Libs\DotEnv\Stubs\AppUrl;
use EnsphereCore\Libs\DotEnv\Stubs\FilesystemRoot;
use EnsphereCore\Libs\Exceptions\Bucket;
use EnsphereCore\Libs\Exceptions\Coverage\CSRFTokenMismatch;
use EnsphereCore\Libs\Helpers\Contracts\Blueprints\HelpersBlueprint;
use EnsphereCore\Libs\Helpers\Contracts\Helpers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use EnsphereCore\Commands\Ensphere\Rename\Command as RenameCommand;
use EnsphereCore\Commands\Ensphere\Export\Command as ExportCommand;
use EnsphereCore\Commands\Ensphere\Import\Command as ImportCommand;
use EnsphereCore\Commands\Ensphere\Bower\Command as BowerCommand;
use EnsphereCore\Commands\Ensphere\Migrate\Command as MigrateCommand;
use EnsphereCore\Commands\Ensphere\Registration\Command as RegistrationCommand;
use EnsphereCore\Commands\Ensphere\Install\Command as InstallCommand;
use EnsphereCore\Commands\Ensphere\Install\Update\Command as UpdateCommand;
use EnsphereCore\Commands\Ensphere\Make\Command as MakeCommand;
use EnsphereCore\Commands\Ensphere\Database\Command as DatabaseCommand;
use EnsphereCore\Commands\Ensphere\Modules\Command as ModulesCommand;
use EnsphereCore\Commands\Ensphere\ExternalAssets\Command as ExternalAssetsCommand;
use EnsphereCore\Commands\Ensphere\Extend\Command as ExtendCommand;
use EnsphereCore\Commands\Ensphere\Back\Resource\Command as BackResourceCommand;
use EnsphereCore\Libs\DotEnv\Registrar;
use EnsphereCore\Libs\DotEnv\Commands\DotEnv;
use EnsphereCore\Libs\Extending\Illuminate\Routing\UrlGenerator;
use EnsphereCore\Libs\Processor\Registrar as ProcessorRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->extendIlluminateRouting();

        $this->app->booted( function( $app )
        {

            app( ProcessorRegistrar::class )->processPreArtisan();

            $schedule = $app->make( Schedule::class );
            $schedule->command( 'ensphere:external-assets' )->hourly();

            $app[Registrar::class]->add( new FilesystemRoot );
            $app[Registrar::class]->add( new AppUrl );
            $app['ensphere.exception.handler']->addHandler( new CSRFTokenMismatch() );

        });

        view()->addLocation( __DIR__ . '/Libs/Extending/Illuminate/views' );

        $this->publishes([
            __DIR__ . '/../ensphere.assets.json' => base_path( 'EnsphereCore/ensphere-assets.json' ),
            __DIR__ . '/../ensphere.registration.json' => base_path( 'EnsphereCore/ensphere-registration.json' ),
            __DIR__ . '/../ensphere.external.assets.json' => base_path( 'EnsphereCore/ensphere-external-assets.json' )
        ], 'config' );

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton( Registrar::class, function( $app ){
            return new Registrar;
        });

        $this->app->singleton( HelpersBlueprint::class, Helpers::class );

        $this->app->singleton( 'ensphere.exception.handler', function( $app ) {
            return new Bucket();
        });

        $this->app->singleton( ProcessorRegistrar::class, function( $app ) {
            return new ProcessorRegistrar;
        });

        $this->commands([
            RenameCommand::class,
            ExportCommand::class,
            ImportCommand::class,
            BowerCommand::class,
            MigrateCommand::class,
            RegistrationCommand::class,
            InstallCommand::class,
            UpdateCommand::class,
            MakeCommand::class,
            DatabaseCommand::class,
            ModulesCommand::class,
            ExternalAssetsCommand::class,
            DotEnv::class,
            ExtendCommand::class,
            InformCentralHub::class,
            PostProcessCommand::class,
            PreProcessCommand::class,
            BackResourceCommand::class
        ]);
    }

    /**
     * @return void
     */
    protected function extendIlluminateRouting()
    {
        $this->app[ 'url' ] = $this->app->share( function($app)
        {
            $routes = $app[ 'router' ]->getRoutes();
            $app->instance('routes', $routes);

            $url = new UrlGenerator(
                $routes, $app->rebinding(
                    'request', $this->requestRebinder()
                )
            );

            $url->setSessionResolver( function() {
                return $this->app[ 'session' ];
            });

            $app->rebinding( 'routes', function( $app, $routes )
            {
                $app[ 'url' ]->setRoutes( $routes );
            });

            return $url;
        });
    }

    /**
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ( $app, $request ) {
            $app[ 'url' ]->setRequest( $request );
        };
    }

}
