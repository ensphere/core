<?php

namespace EnsphereCore\Libs\Exceptions;

class Bucket
{

    protected $ignore = [];

    protected $handlers = [];

    /**
     * @param Handler $handler
     */
    public function addHandler( Handler $handler )
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return mixed
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

}
