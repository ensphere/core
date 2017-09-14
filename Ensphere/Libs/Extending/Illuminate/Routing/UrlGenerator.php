<?php

namespace EnsphereCore\Libs\Extending\Illuminate\Routing;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Contracts\Routing\UrlGenerator as Blueprint;

class UrlGenerator extends UrlGeneratorContract implements Blueprint
{

    /**
     * @var array
     */
    protected $globalParameters = [];

    /**
     * @param $parameters
     * @return void
     */
    public function setGlobalParameters( $parameters )
    {
        $this->globalParameters = $this->formatParameters( $parameters );
    }

    /**
     * Get the URL for a given route instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $parameters
     * @param  bool   $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    protected function toRoute($route, $parameters, $absolute)
    {

        $parameters = $this->formatParameters($parameters);

        if( ! $parameters ) {
            $parameters += [ null ] + $this->globalParameters;
        } else {
            $parameters += $this->globalParameters;
        }

        $domain = $this->getRouteDomain($route, $parameters);

        $uri = $this->addQueryString($this->trimUrl(
            $root = $this->replaceRoot($route, $domain, $parameters),
            $this->replaceRouteParameters($route->uri(), $parameters)
        ), $parameters);

        if (preg_match('/\{.*?\}/', $uri)) {
            throw UrlGenerationException::forMissingParameters($route);
        }

        $uri = strtr(rawurlencode($uri), $this->dontEncode);

        return $absolute ? $uri : '/'.ltrim(str_replace($root, '', $uri), '/');
    }

    /**
     * Returns the global parameters as a string
     *
     * @return string
     */
    public function getGlobalParametersAsString()
    {
        return http_build_query( $this->globalParameters );
    }

    /**
     * @return string
     */
    public function getGlobalParametersForForm()
    {
        return view( 'illuminate.routing.global', [ 'parameters' => $this->globalParameters ] )->render();
    }

}
