<?php

namespace EnsphereCore\Libs\Exceptions;

use Illuminate\Http\Request;

abstract class Handler
{

    private $exception;

    private $request;

    protected $dontReport = [];

    protected $toHandle;

    /**
     * @param $e
     * @param Request $request
     */
    final public function setException( $e, Request $request )
    {
        $this->exception = $e;
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    final public function getException()
    {
        return $this->exception;
    }

    /**
     * @return mixed
     */
    final public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return array
     */
    final public function getNonReportingExceptions()
    {
        return $this->dontReport;
    }

    /**
     * @return bool
     */
    final public function wantsToHandleIt()
    {
        return strcmp( get_class( $this->exception ), $this->toHandle ) === 0;
    }

    /**
     * @return null
     */
    public function handle() {
        return null;
    }

}
