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

}
