<?php

namespace EnsphereCore\Commands\Ensphere\Process;

use EnsphereCore\Libs\Processor\Registrar;
use Illuminate\Console\Command;

class PostProcessCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ensphere:post-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs bound closures after composer update/install';

    /**
     *
     */
    public function fire()
    {
        if( env( 'COMPOSER_IS_RUNNING', false ) ) {
            if( app()->runningInConsole() ) {
                app( Registrar::class )->processPostComposer();
                $this->info( "Ensphere post-processors successfully ran..." );
            }
        }
    }

}
