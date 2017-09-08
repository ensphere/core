<?php

namespace EnsphereCore\Libs\Extending\LaravelCollective;

use Collective\Html\HtmlServiceProvider as ServiceProvider;

class HtmlServiceProvider extends ServiceProvider
{

    protected function registerFormBuilder()
    {
        $this->app->singleton( 'form', function( $app ) {
            $form = new FormBuilder( $app[ 'html' ], $app[ 'url' ], $app[ 'view' ], $app[ 'session.store' ]->getToken() );
            return $form->setSessionStore( $app[ 'session.store' ] );
        });
    }

}
