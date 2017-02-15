<?php

/**
 * In the AppServiceProvider in the boot method you can assign these via:
 *
 * use EnsphereCore\Libs\DotEnv\Registrar;
 * use EnsphereCore\Libs\DotEnv\Test\AcmeDatabaseUser;
 *
 * $this->app[Registrar::class]->add( new AcmeDatabaseUser );
 *
 */

namespace EnsphereCore\Libs\DotEnv\Test;

use EnsphereCore\Libs\DotEnv\Property;

class AcmeDatabaseUser extends Property
{

    protected $key = 'acme database user';

    protected $defaultValue = 'root';

    protected $description = 'This is a test database username';

}
