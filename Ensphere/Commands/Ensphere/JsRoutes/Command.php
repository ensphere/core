<?php

namespace EnsphereCore\Commands\Ensphere\JsRoutes;

use Illuminate\Console\Command as IlluminateCommand;
use EnsphereCore\Commands\Ensphere\Traits\Module as ModuleTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Command extends IlluminateCommand
{

    use ModuleTrait;

    /**
     * @var string
     */
    protected $name = 'ensphere:jsroutes';

    /**
     * @var string
     */
    protected $description = 'Creates json file for using laravel routes in Javascript';

    /**
     * Fire
     */
    public function fire()
    {
        $routes = [];
        foreach( app( 'routes' )->getRoutes() as $route ) {
            $routeName = $route->getName();
            if( ! $routeName ) continue;
            $routes[ $routeName ] = [
                'path' => $route->getPath(),
                'uri_variables' => $route->parameterNames()
            ];
        }
        file_put_contents( public_path( 'routes.json' ), json_encode( $routes ) );
        $this->info( 'Javascript routes compiled...' );
    }

}
