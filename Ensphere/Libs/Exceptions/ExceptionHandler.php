<?php

namespace EnsphereCore\Libs\Exceptions;

use Exception;
use Whoops\Run;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Response;
use Whoops\Handler\PrettyPageHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as IlluminateExceptionHandler;

class ExceptionHandler extends IlluminateExceptionHandler
{

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        HttpResponseException::class
    ];

    protected $bucket = [];

    /**
     * Handler constructor.
     * @param LoggerInterface $log
     */
    public function __construct( LoggerInterface $log )
    {
        $this->bucket = app( 'ensphere.exception.handler' );
        foreach( $this->bucket->getHandlers() as $handler ) {
            $this->dontReport = array_merge( $this->dontReport, $handler->getNonReportingExceptions() );
        }
        parent::__construct( $log );
    }

    /**
     * @param Exception $e
     */
    public function report( Exception $e )
    {
        parent::report( $e );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Exception $e
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     */
    public function render( $request, Exception $e )
    {
        foreach( $this->bucket->getHandlers() as $handler ) {
            $handler->setException( $e, $request );
            if( $handler->wantsToHandleIt() ) {
                if( ! is_null( $response = $handler->handle() ) ) return $response;
            }
        }
        if ( $this->isHttpException( $e ) ) {
            return $this->renderHttpException( $e );
        }
        if ( config( 'app.debug' ) ) {
            if( ! in_array( get_class( $e ), $this->dontReport ) ) {
                return $this->renderExceptionWithWhoops( $e );
            }
        }
        return parent::render( $request, $e );
    }

    /**
     * @param Exception $e
     * @return \Illuminate\Http\Response
     */
    protected function renderExceptionWithWhoops( Exception $e )
    {
        $whoops = new Run;
        $whoops->pushHandler( new PrettyPageHandler() );
        return new Response( $whoops->handleException( $e ), $e->getStatusCode(), $e->getHeaders() );
    }

}
