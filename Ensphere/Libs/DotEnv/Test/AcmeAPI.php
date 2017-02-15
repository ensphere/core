<?php

/**
 * In the AppServiceProvider in the boot method you can assign these via:
 *
 * use EnsphereCore\Libs\DotEnv\Registrar;
 * use EnsphereCore\Libs\DotEnv\Test\AcmeAPI;
 *
 * $this->app[Registrar::class]->add( new AcmeAPI );
 *
 */

namespace EnsphereCore\Libs\DotEnv\Test;

use EnsphereCore\Libs\DotEnv\Property;

class AcmeAPI extends Property
{

    protected $key = 'acme api';

    protected $defaultValue = 'sfgdfhrrt653546474fdfdgfgdfsda';

    protected $description = 'This is a test api key';

}
