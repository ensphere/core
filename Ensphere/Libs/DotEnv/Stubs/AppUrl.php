<?php

namespace EnsphereCore\Libs\DotEnv\Stubs;

use EnsphereCore\Libs\DotEnv\Property;

class AppUrl extends Property
{

    protected $key = 'app url';

    protected $defaultValue = 'http://localhost:8000';

    protected $description = 'Defines the applications domain/url. This is a required property';

    protected $owner = 'ensphere/core';

}
