<?php

namespace EnsphereCore\Commands\Ensphere\Process;

use EnsphereCore\Libs\Processor\Registrar;
use Illuminate\Console\Command;

class PreProcessCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ensphere:pre-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs bound closures before composer update/install';

    /**
     *
     */
    public function fire()
    {
        if( env( 'COMPOSER_IS_RUNNING', false ) ) {
            if( app()->runningInConsole() ) {
                app( Registrar::class )->processPreComposer();
                $this->info( "Ensphere pre-processors successfully ran..." );
            }
        }
    }

}
