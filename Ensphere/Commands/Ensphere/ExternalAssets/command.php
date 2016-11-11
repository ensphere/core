<?php namespace EnsphereCore\Commands\Ensphere\ExternalAssets;

use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Command extends IlluminateCommand {

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name = 'ensphere:external-assets';

	/**
	 * [$description description]
	 * @var string
	 */
	protected $description = 'Retrieves external assets and stores localy';

	protected $externalPath = '';

	/**
	 * [__construct description]
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * [fire description]
	 * @return [type] [description]
	 */
	public function fire()
	{
		$this->checkStorageFolder();
		$this->getExternalFilesAndStoreLocally();
	}

	/**
	 * [checkStorageFolder description]
	 * @return [type] [description]
	 */
	protected function checkStorageFolder()
	{
		$this->externalPath = public_path( 'external' );
		if( ! file_exists( $this->externalPath ) ) {
			$this->info( 'creating local storage folder for external assets...' );
			mkdir( $this->externalPath, 0777 );
		}
	}

	/**
	 * [getExternalFilesAndStoreLocally description]
	 * @return [type] [description]
	 */
	protected function getExternalFilesAndStoreLocally()
	{
		$assets =  base_path( 'EnsphereCore/ensphere-external-assets.json' );
		if( ! file_exists( $assets ) ) return $this->info( 'no external images file found..' );
		$data = json_decode( file_get_contents( $assets ) );
		foreach( $data->assets as $asset ) {
			if( $contents = @file_get_contents( $asset ) ) {
				$this->info( "fetching {$asset}..." );
				$extension = ( preg_match( "#css(?:\?.+)$#is", $asset ) ? 'css' : 'js' );
				file_put_contents( public_path( '/external/' . sha1( $asset ) . ".{$extension}" ), $contents );
			}
		}
	}

}