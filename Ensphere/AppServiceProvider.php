<?php namespace EnsphereCore;

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

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app->booted( function() {
			$schedule = $this->app->make( Schedule::class );
			$schedule->command( 'ensphere:external-assets' )->everyHour();
		});
		$this->publishes([
			__DIR__ . '/../ensphere.assets.json' => base_path( 'EnsphereCore/ensphere-assets.json' ),
			__DIR__ . '/../ensphere.registration.json' => base_path( 'EnsphereCore/ensphere-registration.json' )
		], 'config' );
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
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
		]);
	}
}
