<?php

namespace EnsphereCore\Libs\DotEnv;


abstract class Property
{

    protected $key = '';

    protected $defaultValue = '';

    protected $description = '';

    protected $acceptedValues = [];

    /**
     * @return string
     */
    final public function getKey()
    {
        return strtoupper( str_slug( $this->key, '_' ) );
    }

    /**
     * @return mixed
     */
    final public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    final public function getDescription()
    {
        return $this->description;
    }

    final public function getAcceptedValues()
    {
        return $this->acceptedValues;
    }

}
