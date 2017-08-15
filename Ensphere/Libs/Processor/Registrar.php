<?php

namespace EnsphereCore\Libs\Processor;

use Closure;

class Registrar
{

    /**
     * @var array
     */
    private $preArtisanProcessors = [];

    /**
     * @var array
     */
    private $postArtisanProcessors = [];

    /**
     * @var array
     */
    private $preComposerProcessors = [];

    /**
     * @var array
     */
    private $postComposerProcessors = [];

    /**
     * @var array
     */
    private $preHttpProcessors = [];

    /**
     * @var array
     */
    private $postHttpProcessors = [];

    /**
     * @param $process
     * @return void
     */
    public function addPreArtisan( Closure $process )
    {
        array_push( $this->preArtisanProcessors, $process );
    }

    /**
     * @param $process
     * @return void
     */
    public function addPreComposer( Closure $process )
    {
        array_push( $this->preComposerProcessors, $process );
    }

    /**
     * @param $process
     * @return void
     */
    public function addPostArtisan( Closure $process )
    {
        array_push( $this->postArtisanProcessors, $process );
    }

    /**
     * @param $process
     * @return void
     */
    public function addPostComposer( Closure $process )
    {
        array_push( $this->postComposerProcessors, $process );
    }

    /**
     * @param $process
     * @return void
     */
    public function preHttp( Closure $process )
    {
        array_push( $this->preHttpProcessors, $process );
    }

    /**
     * @param $process
     * @return void
     */
    public function postHttp( Closure $process )
    {
        array_push( $this->postHttpProcessors, $process );
    }

    /**
     * @return void
     */
    public function processPreComposer()
    {
        foreach( $this->preComposerProcessors as $process ) {
            $process();
        }
    }

    /**
     * @return void
     */
    public function processPostComposer()
    {
        foreach( $this->postComposerProcessors as $process ) {
            $process();
        }
    }

    /**
     * @return void
     */
    public function processPreArtisan()
    {
        if( ! env( 'COMPOSER_IS_RUNNING', false ) ) {
            if( app()->runningInConsole() ) {
                foreach( $this->preArtisanProcessors as $process ) {
                    $process();
                }
            }
        }
    }

    /**
     * @return void
     */
    public function processPostArtisan()
    {
        if( ! env( 'COMPOSER_IS_RUNNING', false ) ) {
            if( app()->runningInConsole() ) {
                foreach( $this->postArtisanProcessors as $process ) {
                    $process();
                }
            }
        }
    }

}
