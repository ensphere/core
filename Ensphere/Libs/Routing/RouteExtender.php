<?php

namespace EnsphereCore\Libs\Routing;

use Illuminate\Routing\Router;

class RouteExtender
{

    /**
     * Module Router Object
     *
     * @var \Illuminate\Routing\Router;
     */
    protected $router;

    /**
     * RouteExtender constructor.
     *
     * @param Router $router
     */
    public function __construct( Router $router )
    {
        $this->router = $router;
    }

    /**
     * @param $name
     * @param $namespace
     */
    public function setNamespace( $name, $namespace )
    {
        $route = $this->getRoute( $name );
        $this->setRouteAction( $route, 'namespace', $namespace );
        $this->alterUsesForNewNamespace( $route );
    }

    /**
     * @param $name
     * @param $prefix
     */
    public function setPrefix( $name, $prefix )
    {
        $prefix = trim( $prefix, '/' );
        $route = $this->getRoute( $name );
        $uri = ltrim( str_replace( ltrim( $route->getPrefix(), '/' ), '', $route->getUri() ), '/' );
        $this->setRouteAction( $route, 'prefix', $prefix );
        $route->setUri( trim( "{$prefix}/{$uri}", '/' ) );
    }

    /**
     * @param $name
     * @param $prefix
     */
    public function setPrefixForAllMatching( $name, $prefix )
    {
        $routes = $this->getAllRoutesMatching( $name );
        foreach( $routes as $route ) {
            $this->setPrefix( $route->getName(), $prefix );
        }
    }

    /**
     * @param $name
     * @return array
     */
    protected function getAllRoutesMatching( $name )
    {
        $_routes = [];
        $routes = $this->router->getRoutes();
        foreach( $routes->get() as $route ) {
            if( ! is_null( $routeName = $route->getName() ) ) {
                if( preg_match( "#^" . str_replace( [ '\*', '*' ], [ '*', '.?' ], preg_quote( $name, '#' ) ) . "#is", $routeName ) ) {
                    $_routes[] = $route;
                }
            }
        }
        return $_routes;
    }

    /**
     * @param $route
     */
    protected function alterUsesForNewNamespace( $route )
    {
        $actions = $route->getAction();
        $controllerAndAction = array_reverse( explode( '\\', $actions['uses'] ) )[0];
        $this->setRouteAction( $route, 'uses', $actions['namespace'] . '\\' . $controllerAndAction );
        $this->setRouteAction( $route, 'controller', $actions['namespace'] . '\\' . $controllerAndAction );
    }

    /**
     * @param $name
     * @param $uses
     */
    public function setUses( $name, $uses )
    {
        $route = $this->getRoute( $name );
        if( $uses[0] !== '\\' ) {
            $actions = $route->getAction();
            $uses = $actions['namespace'] . '\\' . $uses;
        }
        $this->setRouteAction( $route, 'uses', $uses );
        $this->setRouteAction( $route, 'controller', $uses );
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getRoute( $name )
    {
        return $this->router->getRoutes()->getByName( $name );
    }

    /**
     * @param \Illuminate\Routing\Router $route
     * @param string$action
     * @param string $value
     * @return void
     */
    protected function setRouteAction( $route, $action, $value )
    {
        $actions = $route->getAction();
        $actions[ $action ] = $value;
        $route->setAction( $actions );
    }

}
