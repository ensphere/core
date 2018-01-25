<?php

namespace EnsphereCore\Commands\Ensphere\ExternalAssets;

use Illuminate\Console\Command as IlluminateCommand;

class Command extends IlluminateCommand
{

    /**
     * @var string
     */
    protected $name = 'ensphere:external-assets';

    /**
     * @var string
     */
    protected $description = 'Retrieves external assets and stores localy';

    /**
     * @var string
     */
    protected $externalPath = '';

    /**
     * Command constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function fire()
    {
        $this->checkStorageFolder();
        $this->getExternalFilesAndStoreLocally();
    }

    /**
     * @return void
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
     * @param $content
     * @return string
     */
    public static function detectExtensionByContent( $content )
    {
        if( preg_match( "/[@\.#][a-z][\d\w-]+\s*{/i", $content ) ) return 'css';
        if( preg_match( "/[\s(,;]this\.[\w]+\s*[=]/i", $content ) ) return 'js';
    }

    /**
     * @param $rel
     * @param $base
     * @return string
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
     * @return void
     */
    protected function getExternalFilesAndStoreLocally()
    {
        $assets =  base_path( 'EnsphereCore/ensphere-external-assets.json' );
        if( ! file_exists( $assets ) ) {
            return $this->info( 'no external images file found..' );
        }
        $data = json_decode( file_get_contents( $assets ) );

        foreach( $data->css as $asset ) {
            if( $contents = @file_get_contents( $asset->rel ) ) {
                $this->info( "fetched {$asset->rel}..." );
                if( preg_match_all( "#url\(['\"']?([^\'\"')]+)['\"']?\)#is", $contents, $matches ) ) {
                    $path = preg_replace( "#(.+)/[^/]+\.css#is", "$1/", $asset->rel );
                    foreach( $matches[1] as $assetPath ) {
                        $newFilePath = $this->rel2abs( $assetPath, ltrim( $path, '/' ) );
                        $newFilePath = preg_replace( "#^http://(www\.)?#", "//", $newFilePath );
                        $contents = preg_replace( "#\(['\"']?" . preg_quote( $assetPath, "#" ) . "['\"']?\)#", "('" . $newFilePath . "')", $contents, 1 );
                    }
                }
                if( file_put_contents( public_path( $asset->loc ), $contents ) ) {
                    $this->info( $asset->loc . ' - stored locally!' );
                }
            }
        }
        foreach( $data->js as $asset ) {
            if( $contents = @file_get_contents( $asset->rel ) ) {
                $this->info( "fetched {$asset->rel}..." );
                if( file_put_contents( public_path( $asset->loc ), $contents ) ) {
                    $this->info( $asset->loc . ' - stored locally!' );
                }
            }
        }
    }

}
