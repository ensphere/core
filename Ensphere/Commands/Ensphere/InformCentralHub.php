<?php

namespace EnsphereCore\Commands\Ensphere;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use GuzzleHttp\Client;
use Exception;
use STDClass;

class InformCentralHub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inform:hub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends application and module information to central hub';

    /**
     * @var string
     */
    protected $encryptionKey = 'base64:qKBpyYiWrVexrhb1P/wwPwio1ubMTGwYa7+TZcn7YeY=';

    /**
     * @var string
     */
    protected $hubUrl = 'https://ensphere.purposemedia.co.uk';

    /**
     * @var
     */
    protected $modules_file;

    /**
     * @var
     */
    protected $lock_file;

    /**
     * @var
     */
    protected $composerFile;


    /**
     * InformCentralHub constructor.
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
        if( app()->isLocal() ) {
            $this->info( "no need to inform hub of local development..." );
            return;
        }
        try {
            $this->modules_file = base_path( 'modules.json' );
            $this->lock_file = base_path( 'composer.lock' );
            $this->composerFile = base_path( 'composer.json' );
            $data = [
                'domain' => config( 'app.url' ),
                'server' => gethostbyname( config( 'app.url' ) ),
                'installed' => $this->getCurrentVersions()
            ];
            $encrypter = new Encrypter( base64_decode( substr( $this->encryptionKey, 7 ) ), config( 'app.cipher' ) );
            $payload = $encrypter->encrypt( json_encode( $data ) );
            $httpClient = new Client( [ 'base_uri' => $this->hubUrl ] );
            $res = $httpClient->request( 'POST', '/endpoints/site-data', [
                'form_params' => [
                    'payload' => $payload
                ]
            ] );
            $this->info( 'central hub informed...' );
        } catch( Exception $e ) {
            $this->info( 'hub packet failed...' );
        }
    }

    /**
     * @return array
     */
    protected function getCurrentVersions()
    {
        $packages = json_decode( file_get_contents( $this->lock_file ) )->packages;
        $modules = $this->getModules();
        $installed = [];
        foreach( $packages as $package ) {
            if( isset( $modules[ $package->name ] ) ) {
                $installed[] = [
                    'module' => $package->name,
                    'installed' => $package->version,
                    'required' => $modules[ $package->name ]
                ];
            }
        }
        return $installed;
    }

    /**
     * @return mixed
     */
    protected function getModules()
    {
        return (array) json_decode( file_get_contents( $this->composerFile ) )->require;
    }

    /**
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:' . base64_encode( random_bytes(
                config( 'app.cipher' ) == 'AES-128-CBC' ? 16 : 32
            ) );
    }
}
