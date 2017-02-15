<?php

/**
 * In the AppServiceProvider in the boot method you can assign these via:
 *
 * use EnsphereCore\Libs\DotEnv\Registrar;
 * use EnsphereCore\Libs\DotEnv\Test\AcmeSuperMode;
 *
 * $this->app[Registrar::class]->add( new AcmeSuperMode );
 *
 */

namespace EnsphereCore\Libs\DotEnv\Test;

use EnsphereCore\Libs\DotEnv\Property;

class AcmeSuperMode extends Property
{

    protected $key = 'acme super mode';

    protected $defaultValue = 'false';

    protected $description = 'This sets you app to supper dooper mode!';

    protected $acceptedValues = [ 'true', 'false' ];


}
