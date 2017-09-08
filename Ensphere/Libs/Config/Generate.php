<?php

namespace EnsphereCore\Libs\Config;

use Collective\Html\HtmlServiceProvider;
use EnsphereCore\Libs\Extending\LaravelCollective\HtmlServiceProvider as HtmlServiceProviderOverride;

class Generate
{

    /**
     * @var array
     */
    static $swapProviders = [
        HtmlServiceProvider::class => HtmlServiceProviderOverride::class
    ];

    /**
     * @param $laravelProviders
     * @param array $appProviders
     * @return array
     */
    public static function providers( $laravelProviders, $appProviders = array() ) {
        $packageProviders = array();
        $path = base_path('config/packages.json');
        if( file_exists( $path ) ) {
            $data = json_decode( file_get_contents( $path ) );
            if( isset( $data->providers ) ) {
                $packageProviders = $data->providers;
            }
        }
        $return = array_merge( $laravelProviders, $packageProviders, $appProviders );
        if ( strpos( php_sapi_name(), 'cli' ) !== false ) {
            foreach( $return as $key => $provider ) {
                if( ! class_exists( $provider ) ) {
                    unset( $return[ $key ] );
                }
            }
        }
        foreach( self::$swapProviders as $remove => $replace ) {
            if( ( $key = array_search( $remove, $return ) ) !== false ) {
                $return[ $key ] = $replace;
            }
        }
        return $return;
    }

    /**
     * @param array $appAliases
     * @return array
     */
    public static function aliases( $appAliases = array() ) {
        $packageAliases = array();
        $path = base_path('config/packages.json');
        if( file_exists( $path ) ) {
            $data = json_decode( file_get_contents( $path ) );
            if( isset( $data->aliases ) ) {
                $packageAliases = (array)$data->aliases;
            }
        }
        return array_merge( $packageAliases, $appAliases );
    }

}
