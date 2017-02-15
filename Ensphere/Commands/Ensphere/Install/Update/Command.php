<?php namespace EnsphereCore\Commands\Ensphere\Install\Update;

use EnsphereCore\Commands\Ensphere\Traits\Module as ModuleTrait;
use Illuminate\Console\Command as IlluminateCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Storage, File;

class Command extends IlluminateCommand {

	use ModuleTrait;

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name = 'ensphere:update';

	/**
	 * [$description description]
	 * @var string
	 */
	protected $description = 'Update vendor and application dependencies.';

	/**
	 * [$currentStructure description]
	 * @var [type]
	 */
	private $currentStructure;

	/**
	 * [fire description]
	 * @return [type] [description]
	 */
	public function fire()
	{
		$this->generateRegistrationFile();
		$this->publishVendorAssets();
		$this->combineDependencyAssets();
		$this->migrateRun();
		$this->generateDotEnvFile();
	}

	private function generateDotEnvFile()
    {
        $this->info('generating .env file...');
        $this->info( shell_exec( "php artisan ensphere:dotenv generate" ) );
    }

	/**
	 * [migrateRun description]
	 * @return [type] [description]
	 */
	private function migrateRun()
	{
		$this->info( shell_exec( "php artisan ensphere:migrate run" ) );
	}

	/**
	 * [generateRegistrationFile description]
	 * @return [type] [description]
	 */
	private function generateRegistrationFile()
	{
		$this->info('generating registration file...');
		$this->info( shell_exec( "php artisan ensphere:register" ) );
	}

	/**
	 * [publishVendorAssets description]
	 * @return [type] [description]
	 */
	private function publishVendorAssets()
	{
		$this->info('publishing config files...');
        //$this->deleteNonModulesVendorAssets();
		$this->cleanModulePackageAssetFolders();
		$this->info( shell_exec( "php artisan vendor:publish --tag=config" ) );
		$this->info('pushing module assets to application...');
		$this->info( shell_exec( "php artisan vendor:publish --tag=forced --force" ) );
	}

	protected function deleteNonModulesVendorAssets()
	{
		$modulePackages = $this->getPackages( base_path( 'EnsphereCore' ) );
		$it = new RecursiveDirectoryIterator( public_path( 'vendor' ) );
		$paths = [];
		foreach( $it as $file ) {
			if( $file->isDir() && ! in_array( $file->getFilename(), [ '.', '..' ] ) ) {
				$paths[$file->getBasename()] = $file->getRealPath();
			}
		}
		foreach( $modulePackages as $name => $detail ) {
			if( isset( $paths[$name] ) ) {
				unset( $paths[$name] );
			}
		}
		foreach( $paths as $path ) {
			File::cleanDirectory( $path );
			Storage::deleteDirectory( $path );
			rmdir( $path );
		}
	}

    /**
     * [getPackages description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    private function getPackages( $path ) {
        return json_decode( file_get_contents( $path . '/ensphere-assets.json' ) );
    }

	/**
	 * [cleanModulePackageAssetFolders description]
	 * @return [type] [description]
	 */
	protected function cleanModulePackageAssetFolders()
	{
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( public_path( 'package' ) ), RecursiveIteratorIterator::SELF_FIRST );
		$thisData = $this->getCurrentVendorAndModuleName();
		foreach( $iterator as $file ) {
			if( ! in_array( $file->getBasename(), [ '.', '..' ]) && $file->isDir() ) {
				$relPath = ltrim( str_replace( public_path( 'package' ), '', $file->getPathname() ), '/' );
				if( count( explode( "/", $relPath ) ) === 2 ) {
					if( $thisData['vendor'] . '/' . $thisData['module'] !== $relPath ) {
						$this->deleteFolderAndContents( $file->getPathname() );
					}
				}
			}
		}
	}

	/**
	 * [deleteFolderAndContents description]
	 * @param  [type] $folderPath [description]
	 * @return [type]             [description]
	 */
	protected function deleteFolderAndContents( $folderPath )
	{
		$it = new RecursiveDirectoryIterator( $folderPath, RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
		foreach( $files as $file ) {
			if ( $file->isDir() ){
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}
	}

	/**
	 * [combineVendorAssets description]
	 * @return [type] [description]
	 */
	private function combineDependencyAssets()
	{
		$this->info('generating dependency config...');
		$this->info( shell_exec( "php artisan ensphere:bower" ) );
	}

	/**
	 * [getArguments description]
	 * @return [type] [description]
	 */
	protected function getArguments()
	{
		return [];
	}

	/**
	 * [getOptions description]
	 * @return [type] [description]
	 */
	protected function getOptions()
	{
		return [];
	}

}
