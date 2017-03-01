<?php

namespace EnsphereCore\Libs\Exceptions\Coverage;

use EnsphereCore\Libs\Exceptions\Handler;

class CSRFTokenMismatch extends Handler
{

    /**
     * @var string
     */
    protected $toHandle = 'Illuminate\Session\TokenMismatchException';

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handler()
    {
        return back()->withErrors( [ 'token_mismatch' => 'Form Token Session expired, please re-enter your details' ] )->withInput( request()->except( '_token' ) );
    }
}
