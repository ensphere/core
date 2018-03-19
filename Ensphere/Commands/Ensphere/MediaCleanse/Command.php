<?php

namespace EnsphereCore\Commands\Ensphere\MediaCleanse;

use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Facades\Storage;
use Purposemedia\AdminMediaManager\Contracts\Blueprints\MediaManager;
use SplFileInfo;

class Command extends IlluminateCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean the media files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if( ! $this->confirm('BACKUP DATABASE!! Do you wish to continue?')) {
            return $this->info( "Shutdown..." );
        }
        $repo = app( MediaManager::class );
        $checked = [];
        foreach( Storage::files( 'images' ) as $image ) {
            $file = new SplFileInfo( $image );
            $ext  = $file->getExtension();
            $name = $file->getBasename( "." . $ext );
            if( is_null( $line = $repo->model()->whereName( $name )->whereExtension( $ext )->first() ) ) {
                $this->info( "deleting {$image}..." );
                Storage::delete( $image );
            } else {
                $checked[] = $line->id;
            }
        }
        if( $checked ) {
            $repo->model()->whereNotIn( 'id', $checked )->delete();
        }
        $this->info( "Media cleansed!" );
    }
}
