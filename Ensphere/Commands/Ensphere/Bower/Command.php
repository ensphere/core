<?php namespace EnsphereCore\Commands\Ensphere\Bower;

use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Command extends IlluminateCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ensphere:bower';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Compacts Bower Components.';

	/**
	 * [$writePath description]
	 * @var null
	 */
	private $writePath = 'resources/views/';

	/**
	 * [$order description]
	 * @var [type]
	 */
	private $order = [];

	/**
	 * [$ordered description]
	 * @var [type]
	 */
	private $ordered = [];

	/**
	 * [$satisfield description]
	 * @var integer
	 */
	private $satisfield = 0;

	/**
	 * [$bowers description]
	 * @var [type]
	 */
	private $bowers = [];

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->writePath = base_path($this->writePath);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$it = new RecursiveDirectoryIterator( base_path() );
		foreach( new RecursiveIteratorIterator( $it ) as $file ) {
			if( $file->getFilename() === 'ensphere-assets.json' ) {
				$packages = $this->getPackages( $file->getPath() );
				foreach( $packages as $name => $packageData ) {
					$this->bowers[] = new Bower( $name, $packageData );
				}
			}
		}
		// Order the array by dependencies
		$this->order();
		// Generate the blade template
		$this->generateTemplate();
		$this->info('HTTP snippets generated!');
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
	 * [order description]
	 * @return [type] [description]
	 */
	private function order() {
		foreach( $this->bowers as $bower ) {
			$this->order[$bower->name()] = [
				'dependencies' => $bower->getDependencies(),
				'bower' => $bower
			];
		}
		$this->orderItems();
	}

	/**
	 * [orderItems description]
	 * @return [type] [description]
	 */
	private function orderItems(){
		while( ! empty( $this->order ) ) {
			$item = array_splice( $this->order, 0, 1 );
			$data = end( $item );
			$name = key( $item );
			if( empty( $data['dependencies'] ) ) {
				$this->ordered[$name] = $data;
			} else {
				$satisafied = true;
				foreach( $data['dependencies'] as $dependency ) {
					if( isset( $this->order[$dependency] ) ) $satisafied = false;
				}
				if( $satisafied ) {
					$this->ordered[$name] = $data;
				} else {
					$this->order = $this->order + $item;
				}
			}
		}
	}

	/**
	 * [assetLoaderTemplate description]
	 * @param  [type] $jsFiles  [description]
	 * @param  [type] $cssFiles [description]
	 * @return [type]           [description]
	 */
	public static function assetLoaderTemplate( $jsFiles, $cssFiles ) {
		$return = "
		<script type="text/javascript">
		var styles = [ '" . implode( "','", $cssFiles ) . "'];
		var scripts =  [ '" . implode( "','", $jsFiles ) . "'];
		var cb = function() {
				window.loadStyles = function() {
					var href = styles.shift(); var h = document.getElementsByTagName('head')[0]; var l = document.createElement('link');
					l.rel = 'stylesheet'; l.href = href;l.onload = function(){if( styles.length !== 0 ){window.loadStyles();}};h.appendChild(l);
				};
				window.loadScripts = function( _callback ) {
					var callback = _callback || function(){};
					var src = scripts.shift(); var h = document.getElementsByTagName('head')[0]; var html = document.getElementsByTagName('html')[0]; var l = document.createElement('script');
					l.rel = 'text/javascript'; l.src = src;
					l.onload = function(){if( scripts.length !== 0 ) {window.loadScripts( _callback );} else {html.className += ' loaded';callback();}};h.appendChild(l);
				};
				window.loadStyles();
				window.loadScripts(function(){ if( document.body ) $(document).trigger('ready'); if( document.readyState == 'complete' ) $(window).trigger('load'); });
			};
			var raf = requestAnimationFrame || mozRequestAnimationFrame || webkitRequestAnimationFrame || msRequestAnimationFrame;
			if (raf) raf(cb);
			else window.addEventListener('load', cb);
		</script>";
		return $return;
	}

	/**
	 * [assetLoaderTemplateLocal description]
	 * @param  [type] $jsFiles  [description]
	 * @param  [type] $cssFiles [description]
	 * @return [type]           [description]
	 */
	public static function assetLoaderTemplateLocal( $jsFiles, $cssFiles ) {
		$return = '';
		foreach( $cssFiles as $file ) {
			$return .= "<link defer href='{$file}' rel='stylesheet' type='text/css'>\n\r";
		}
		foreach( $jsFiles as $file ) {
			$return .= "<script src='{$file}'></script>\n\r";
		}
		return $return;
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
	 * [externalAssetsToLocalAssets description]
	 * @param  [type] $assetGroups [description]
	 * @return [type]              [description]
	 */
	protected function externalAssetsToLocalAssets( $assetGroups )
	{
		$external = [ 'assets' => [] ];
		$temp = [];
		foreach( $assetGroups['stylesheets.css'] as $key => $stylesheet ) {
			if( preg_match( "#^https?#is", $stylesheet ) ) {
				$external['assets'][] = $stylesheet;
				$assetGroups['stylesheets.css'][$key] = "/external/" . sha1( $stylesheet ) . '.css';
				$temp[] = [ 
					'rel' => $stylesheet,
					'loc' => "/external/" . sha1( $stylesheet ) . '.css'
				];	
			}
		}
		foreach( $assetGroups['javascripts.js'] as $key => $javascript ) {
			if( preg_match( "#^https?#is", $javascript ) ) {
				$external['assets'][] = $javascript;
				$assetGroups['javascripts.js'][$key] = "/external/" . sha1( $javascript ) . '.js';
				$temp[] = [ 
					'rel' => $javascript,
					'loc' => "/external/" . sha1( $javascript ) . '.js'
				];	
			}
		}
		file_put_contents( base_path( 'EnsphereCore/ensphere-external-assets.json' ), json_encode( $external, JSON_PRETTY_PRINT ) );
		$this->call( 'ensphere:external-assets' );
		return $assetGroups;
	}

	/**
	 * [buildCombinedAssets description]
	 * @param  [type] $assetGroups [description]
	 * @return [type]              [description]
	 */
	protected function buildCombinedAssets( $assetGroups, $minify = true )
	{
		$assetGroups = $this->externalAssetsToLocalAssets( $assetGroups );
		foreach( $assetGroups as $saveAs => $assets ) {
			$data = '';
			foreach( $assets as $asset ) {
				$_data = file_get_contents( public_path( ltrim( $asset, '/' ) ) );
				if( $saveAs === 'stylesheets.css' ) {
					if( preg_match_all( "#url\(['\"']?([^\'\"')]+)['\"']?\)#is", $_data, $matches ) ) {
						$path = preg_replace( "#(.+)/[^/]+\.css#is", "$1/", $asset );
						foreach( $matches[1] as $assetPath ) {
							$newFilePath = $this->rel2abs( $assetPath, rtrim( env( 'APP_URL' ), '/' ) . '/' . ltrim( $path, '/' ) );
							$_data = preg_replace( "#\(['\"']?" . preg_quote( $assetPath, "#" ) . "['\"']?\)#", "('" . $newFilePath . "')", $_data, 1 );
						}
					}
					$data .= $_data;
				} else {
					if( $saveAs === 'javascripts.js' ) {
						$data .= "try { \n;(function(){\n" . $_data . "\n})();\n } catch(e) { console.log('[" . $asset . "]: ' + e.message );}\n";
					}
				}
			}
			if( $saveAs === 'javascripts.js' ) {

				file_put_contents( public_path( $saveAs ), $data );

			} else {
				if( $minify ) {
					$minifier = new \MatthiasMullie\Minify\CSS;
					$minifier->add( $data );
					$data = $minifier->minify();
				}
				file_put_contents( public_path( $saveAs ), $data );
			}
		}
	}

	/**
	 * [getNewVersion description]
	 * @return [type] [description]
	 */
	protected function getNewVersion()
	{
		$versionFilePath = base_path( 'asset_version.json' );
		if( ! file_exists( $versionFilePath ) ) {
			file_put_contents( $versionFilePath, '{ "version" : "0000000001" }' );
		}
		$versionFile = json_decode( file_get_contents( $versionFilePath ) );
		$version = (int)$versionFile->version;
		$newVersion = str_pad( ($version+1), 10, "0", STR_PAD_LEFT );
		file_put_contents( $versionFilePath, '{ "version" : "' . $newVersion . '" }' );
		return $newVersion;
	}

	/**
	 * [generateTemplate description]
	 * @return [type] [description]
	 */
	private function generateTemplate()
	{
		if ( \App::environment( 'local' ) ) {
			$js = array_merge( $this->getJavascriptFiles(), $this->getModuleJsFiles() );
			$css = array_merge( $this->getStyleFiles(), $this->getModuleCssFiles() );
			file_put_contents( $this->writePath . 'loader.blade.php', self::assetLoaderTemplateLocal( $js, $css ) );
		} else {
			$newVersion = $this->getNewVersion();
			$this->buildCombinedAssets([
				'javascripts.js' 	=>  array_merge( $this->getJavascriptFiles(), $this->getModuleJsFiles() ),
				'stylesheets.css' 	=> array_merge( $this->getStyleFiles(), $this->getModuleCssFiles() )
			] );
			$js =  ['/javascripts.js?ver=' . $newVersion];
			$css = ['/stylesheets.css?ver=' . $newVersion];
			file_put_contents( $this->writePath . 'loader.blade.php', self::assetLoaderTemplate( $js, $css ) );
		}
	}

	/**
	 * [getModuleJsFiles description]
	 * @return [type] [description]
	 */
	protected function getModuleJsFiles()
	{
		$files = array();
		if( file_exists( public_path( 'package' ) ) ) {
			$it = new RecursiveDirectoryIterator( public_path( 'package' ) );
			foreach( new RecursiveIteratorIterator( $it ) as $file ) {
				if( $file->getExtension() === 'js' ) {
					$files[] = str_replace( public_path(), '', $file->getPathname() );
				}
			}
		}
		return $files;
	}

	/**
	 * [getModuleCssFiles description]
	 * @return [type] [description]
	 */
	protected function getModuleCssFiles()
	{
		$files = array();
		if( file_exists( public_path( 'package' ) ) ) {
			$it = new RecursiveDirectoryIterator( public_path( 'package' ) );
			foreach( new RecursiveIteratorIterator( $it ) as $file ) {
				if( $file->getExtension() === 'css' ) {
					$files[] = str_replace( public_path(), '', $file->getPathname() );
				}
			}
		}
		return $files;
	}

	/**
	 * [getJavascriptFiles description]
	 * @return [type] [description]
	 */
	private function getJavascriptFiles() {
		$files = [];
		foreach( $this->ordered as $data ) {
			$bower = $data['bower'];
			$files = array_merge( $files, $bower->getJavascriptFiles() );
		}
		return $files;
	}

	/**
	 * [getStyleFiles description]
	 * @return [type] [description]
	 */
	private function getStyleFiles() {
		$files = [];
		foreach( $this->ordered as $data ) {
			$bower = $data['bower'];
			$files = array_merge( $files, $bower->getStyleFiles() );
		}
		return $files;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [];
	}

}
