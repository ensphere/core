<?php

namespace EnsphereCore\Libs\Helpers\Contracts;

use EnsphereCore\Libs\Helpers\Contracts\Blueprints\HelpersBlueprint;

use EnsphereCore\Commands\Ensphere\Traits\Module;

class Helpers implements HelpersBlueprint
{

    use Module;

    protected $module;

    /**
     * Helpers constructor.
     */
    public function __construct()
    {
        $this->module = $this->getCurrentVendorAndModuleName();
    }

    /**
     * @param string $basename
     * @return string
     */
    public function publicUrl( $basename = '' )
    {
        return '/package/' . $this->module['vendor'] . '/' . $this->module['module'] . "/{$basename}";
    }

    /**
     * @param string $basename
     * @return mixed
     */
    public function publicImg( $basename = '' )
    {
        return public_url( "images/{$basename}" );
    }

    public function displaySuccessMessage()
    {
        $message = session('success');
        if( $message ) {
            echo '
            <div class="ui success message">
                <div class="header">Success!</div>
                <p>' . $message . '</p>
            </div>';
        }
    }

    /**
     * @param $errors
     */
    public function displayErrorMessages( $errors )
    {

        if( ! $errors->isEmpty() ) {
            echo '
            <div class="ui error message">
                <div class="header">
                    There was some errors with your submission
                </div>
                <ul class="list">';
                    foreach( $errors->all() as $error ) {
                        echo "<li>{$error}</li>";
                    }
                    echo '
                </ul>
            </div>';
        }
    }

}
