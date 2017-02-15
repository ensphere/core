<?php

namespace EnsphereCore\Libs\DotEnv\Stubs;

use EnsphereCore\Libs\DotEnv\Property;

class FilesystemRoot extends Property
{

    protected $key = 'filesystem root';

    protected $defaultValue = 'storage/app';

    protected $description = 'Sets the path for the File System. Typically the back end application is the master and the front end is the slave.';

}
