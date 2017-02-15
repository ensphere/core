<?php

namespace EnsphereCore\Libs\DotEnv\Commands;

use EnsphereCore\Libs\DotEnv\Registrar;
use Illuminate\Console\Command;

class DotEnv extends Command
{

    protected $signature = 'ensphere:dotenv {option}';

    protected $description = ' info|generate - generate the .env file based on the modules or info for information about the settings.';

    public function handle()
    {
        $option = $this->argument( 'option' );
        switch( $option )
        {
            case 'info' :
                app( Registrar::class )->showInfo( $this );
            break;
            case 'generate' :
                app( Registrar::class )->generate();
                $this->info("DotEnv file successfully updated");
            break;
        }
    }

}
