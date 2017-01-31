<?php namespace EnsphereCore\Commands\Ensphere\ExternalAssets;

use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
	 * [detectExtensionByContent description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public static function detectExtensionByContent( $content )
	{
		if( preg_match( "/[@\.#][a-z][\d\w-]+\s*{/i", $content ) ) return 'css';
		if( preg_match( "/[\s(,;]this\.[\w]+\s*[=]/i", $content ) ) return 'js';
	}

	/**
	 * [rel2abs description]
	 * @param  [type] $rel  [description]
	 * @param  [type] $base [description]
	 * @return [type]       [description]
	 */
	protected function rel2abs( $rel, $base )
	{

		// parse base URL  and convert to local variables: $scheme, $host,  $path
		extract( parse_url( $base ) );

		if ( strpos( $rel,"//" ) === 0 ) {
			return $scheme . ':' . $rel;
		}

		// return if already absolute URL
		if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) {
			return $rel;
		}

		// queries and anchors
		if ( $rel[0] == '#' || $rel[0] == '?' ) {
			return $base . $rel;
		}

		// remove non-directory element from path
		$path = preg_replace( '#/[^/]*$#', '', $path );

		// destroy path if relative url points to root
		if ( $rel[0] ==  '/' ) {
			$path = '';
		}

		// dirty absolute URL
		$abs = $host . $path . "/" . $rel;

		// replace '//' or  '/./' or '/foo/../' with '/'
		$abs = preg_replace( "/(\/\.?\/)/", "/", $abs );
		$abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs );

		// absolute URL is ready!
		return $scheme . '://' . $abs;
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
				$info = pathinfo( $asset );
				if( ! isset( $info['extension'] ) ) {
					$extension = self::detectExtensionByContent( $contents );
				} else {
					$extension = $info['extension'];
				}
				if( $extension === 'css' ) {
					if( preg_match_all( "#url\(['\"']?([^\'\"')]+)['\"']?\)#is", $contents, $matches ) ) {
						$path = preg_replace( "#(.+)/[^/]+\.css#is", "$1/", $asset );
						$parse = parse_url( $asset );
						$url = $parse['scheme'] . '://' . $parse['host'];
						foreach( $matches[1] as $assetPath ) {
							$newFilePath = $this->rel2abs( $assetPath, ltrim( $path, '/' ) );
							$newFilePath = preg_replace( "#^http://(www\.)?#", "//", $newFilePath );
							$contents = preg_replace( "#\(['\"']?" . preg_quote( $assetPath, "#" ) . "['\"']?\)#", "('" . $newFilePath . "')", $contents, 1 );
						}
					}
				}
				$this->info( '/external/' . sha1( $asset ) . ".{$extension}" );
				if( ! file_put_contents( public_path( '/external/' . sha1( $asset ) . ".{$extension}" ), $contents ) ) {
					
				}
			}
		}
	}

}