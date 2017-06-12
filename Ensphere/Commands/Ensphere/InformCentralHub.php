<?php

namespace EnsphereCore\Commands\Ensphere;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use GuzzleHttp\Client;
use Exception;

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

    protected $encryptionKey = 'base64:qKBpyYiWrVexrhb1P/wwPwio1ubMTGwYa7+TZcn7YeY=';

    protected $hubUrl = 'https://ensphere.purposemedia.co.uk';

    //protected $hubUrl = 'http://127.0.0.1:8000';

    protected $modules_file;

    protected $lock_file;


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
        if( app()->isLocal() ) return;
        /**
         * No need to inform the hub if its local environment
         */
        try {
            $this->modules_file = base_path( 'modules.json' );
            $this->lock_file = base_path( 'composer.lock' );
            $data = [
                'domain' => config( 'app.url' ),
                'server' => gethostbyname( php_uname( 'n' ) ),
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
            $this->info( 'hub packet failed!' );
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
        if( ! file_exists( $this->modules_file ) ) {
            file_put_contents( $this->modules_file, json_encode( new STDclass, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES ) );
        }
        return (array) json_decode( file_get_contents( $this->modules_file ) );
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
