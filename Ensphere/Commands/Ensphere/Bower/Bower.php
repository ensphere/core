<?php

namespace EnsphereCore\Commands\Ensphere\Bower;

class Bower
{

    /**
     * @var array
     */
    protected $defined_css_file = [];

    /**
     * @var array
     */
    protected $defined_js_file = [];

    /**
     * @var null
     */
    private $bower = null;

    /**
     * @var null|string
     */
    private $basePath = null;

    /**
     * @var mixed|null
     */
    private $uri = null;

    /**
     * @var array
     */
    private $dependencies = [];

    /**
     * @var null
     */
    private $name = null;

    /**
     * @var array
     */
    private $files = [];

    /**
     * Bower constructor.
     * @param $name
     * @param $packageData
     */
    public function __construct( $name, $packageData )
    {
        $this->name = $name;
        $this->dependencies = isset( $packageData->dependencies ) ? $packageData->dependencies : [];
        $this->files = isset( $packageData->files ) ? $packageData->files : [];
        $this->defined_css_file = isset( $packageData->css_files ) ? $packageData->css_files : [];
        $this->defined_js_file = isset( $packageData->js_files ) ? $packageData->js_files : [];
        $this->basePath = public_path("vendor/{$name}/");
        $this->uri = str_replace( public_path(), '', $this->basePath );
    }

    /**
     * @return null
     */
    public function name() {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getDependencies() {
        return $this->dependencies;
    }

    /**
     * @return array
     */
    public function getJavascriptFiles() {
        $javascripts = $this->defined_js_file;
        foreach( $this->files as $file ) {
            if( preg_match( "#\.js$#is", $file ) ) {
                if( preg_match( "#^https?#is", $file ) ) {
                    $javascripts[] = $file;
                } else {
                    $javascripts[] = $this->uri . $file;
                }
            }
        }
        return $javascripts;
    }

    /**
     * @return array
     */
    public function getStyleFiles() {
        $styles = $this->defined_js_file;
        foreach( $this->files as $file ) {
            if( preg_match( "#css(\?.+)?$#is", $file ) ) {
                if( preg_match( "#^https?#is", $file ) ) {
                    $styles[] = $file;
                } else {
                    $styles[] = $this->uri . $file;
                }
            }
        }
        return $styles;
    }

}
