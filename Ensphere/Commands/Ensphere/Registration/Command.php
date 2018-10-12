<?php

namespace EnsphereCore\Commands\Ensphere\Registration;

use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Command extends IlluminateCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ensphere:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register package Middleware and Providers.';

    /**
     * [$writePath description]
     * @var null
     */
    private $writePath = 'config/packages.json';

    /**
     * Command constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->writePath = base_path($this->writePath);
    }

    /**
     * @return void
     */
    public function fire()
    {
        $packages = $this->getPendingPackageDirs();
        if( empty( $packages ) ) {
            return $this->error('no packages require registering.');
        }
        $this->save( $this->getRegistrationData( $packages ) );
        return $this->info('config file generated!');
    }

    /**
     * @return array
     */
    protected function getPendingPackageDirs()
    {
        $files = [];
        $moduleRegistrationFilePath = base_path( 'EnsphereCore/ensphere-registration.json' );
        if( file_exists( $moduleRegistrationFilePath ) ) {
            $files[] = base_path('EnsphereCore');
        }
        $it = new RecursiveDirectoryIterator( base_path( 'vendor' ) );
        foreach( new RecursiveIteratorIterator( $it ) as $file ) {
            if( $file->getFilename() == 'ensphere-registration.json' ) {
                $files[] = $file->getPath();
            }
        }
        return $files;
    }

    /**
     * @param $paths
     * @return array|void
     */
    protected function getRegistrationData( $paths )
    {
        $config = [];
        foreach( $paths as $path ) {
            $data = json_decode( file_get_contents( $path . '/ensphere-registration.json' ) );
            if( ! is_object( $data ) ) {
                return $this->error('incorrect json format: ' . $path . '/ensphere-registration.json');
            }
            $config[] = (array)$data;
        }
        return $this->combineConfigs( $config );
    }

    /**
     * @param $configs
     * @return array
     */
    protected function combineConfigs( $configs )
    {
        $return = [
            'providers' => [],
            'aliases' => [],
            'middleware' => [],
            'routeMiddleware' => [],
            'middlewareGroups' => [],
            'contracts' => []
        ];
        foreach( $configs as $config ) {
            if( isset( $config[ 'providers' ] ) && is_array( $config[ 'providers' ] ) ) {
                foreach( $config[ 'providers' ] as $provider ) {
                    if( ! in_array( $provider, $return[ 'providers' ] ) ) {
                        $return[ 'providers' ][] = $provider;
                    }
                }
            }
            if( isset( $config[ 'aliases' ] ) ) {
                foreach( $config[ 'aliases' ] as $name => $namespace ) {
                    $return[ 'aliases' ][ $name ] = $namespace;
                }
            }
            if( isset( $config[ 'middleware' ] ) && is_array( $config[ 'middleware' ] ) ) {
                foreach( $config[ 'middleware' ] as $middleware ) {
                    if( ! in_array( $middleware, $return[ 'middleware' ] ) ) {
                        $return[ 'middleware' ][] = $middleware;
                    }
                }
            }
            if( isset( $config[ 'routeMiddleware' ] ) ) {
                foreach( $config[ 'routeMiddleware' ] as $name => $namespace ) {
                    $return[ 'routeMiddleware' ][ $name ] = $namespace;
                }
            }
            if( isset( $config[ 'middlewareGroups' ] ) ) {
                foreach( $config[ 'middlewareGroups' ] as $groupName => $groupArray ) {
                    if( ! isset( $return[ 'middlewareGroups' ][ $groupName ] ) ) {
                        $return[ 'middlewareGroups' ][ $groupName ] = [];
                    }
                    $return[ 'middlewareGroups' ][ $groupName ] = array_merge( $return[ 'middlewareGroups' ][ $groupName ], $groupArray );
                }
            }
            if( isset( $config[ 'contracts' ] ) ) {

                $return[ 'contracts' ] = array_merge( $return[ 'contracts' ], (array) $config[ 'contracts' ] );
            }
        }
        $return[ 'aliases' ] = array_unique( $return[ 'aliases' ] );
        $return[ 'routeMiddleware' ] = array_unique( $return[ 'routeMiddleware' ] );
        $return[ 'contracts' ] = array_unique( $return[ 'contracts' ] );
        foreach( $return[ 'middlewareGroups' ] as $key => $val ) {
            if( is_array( $val ) ) {
                $return[ 'middlewareGroups' ][ $key ] = array_unique( $val );
            }
        }
        return $return;
    }

    /**
     * @param $config
     */
    protected function save( $config )
    {
        touch( $this->writePath );
        file_put_contents( $this->writePath, json_encode( $config, JSON_PRETTY_PRINT ) );
    }

}
