<?php

namespace EnsphereCore;

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
use EnsphereCore\Libs\DotEnv\Registrar;
use EnsphereCore\Libs\DotEnv\Commands\DotEnv;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{

		$this->app->booted( function( $app )
        {
			$schedule = $app->make( Schedule::class );
			$schedule->command( 'ensphere:external-assets' )->hourly();

			$app[Registrar::class]->add( new FilesystemRoot );
            $app[Registrar::class]->add( new AppUrl );
            $app['ensphere.exception.handler']->addHandler( new CSRFTokenMismatch() );

		});

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
            DotEnv::class
		]);
	}
}
