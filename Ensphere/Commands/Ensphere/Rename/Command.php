<?php

namespace EnsphereCore\Commands\Ensphere\Rename;

use EnsphereCore\Commands\Ensphere\Traits\Module as ModuleTrait;
use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Exception;

class Command extends IlluminateCommand
{

    use ModuleTrait;

    /**
     * @var string
     */
    protected $description = 'Rename your module';

    /**
     * @var string
     */
    protected $signature = 'ensphere:rename {--vendor= : provide the vendor upfront} {--module= : provide the module name upfront}';

    /**
     * @var string
     */
    private $vendor = '';

    /**
     * @var string
     */
    private $camelCasedVendor = '';

    /**
     * @var string
     */
    private $module = '';

    /**
     * @var string
     */
    private $camelCasedModule = '';

    /**
     * @var string
     */
    private $currentVendor = '';

    /**
     * @var string
     */
    private $currentCamelCasedVendor = '';

    /**
     * @var string
     */
    private $currentModule = '';

    /**
     * @var string
     */
    private $currentCamelCasedModule = '';

    /**
     * Command constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $currentData = $this->getCurrentVendorAndModuleName();
        $this->currentVendor = $currentData['vendor'];
        $this->currentCamelCasedVendor = $currentData['camelCasedVendor'];
        $this->currentModule = $currentData['module'];
        $this->currentCamelCasedModule = $currentData['camelCasedModule'];
    }

    /**
     * @return void
     */
    public function fire()
    {
        if( ! $vendor = $this->option( 'vendor' ) ) {
            $vendor = $this->ask('Whats your Vendor name?');
        }
        $this->vendor = $vendor;
        $this->camelCasedVendor = ucfirst( camel_case( $this->vendor ) );
        if( ! $module = $this->option( 'module' ) ) {
            $module = $this->ask('Whats your Module name?');
        }
        $this->module = $module;
        $this->camelCasedModule = ucfirst( camel_case( $this->module ) );
        if( $this->isOkToRun() ) {
            $this->laravelRename();
            $this->moduleRename();
            $this->dumpAutoload();
            $this->info("done!");
        } else {
            $this->error( 'Cannot rename module, vendor/module alread exists in application!' );
        }
    }

    /**
     * @return bool
     */
    private function isOkToRun()
    {
        return file_exists( base_path("public/package/{$this->vendor}/{$this->module}/" ) ) ? false : true;
    }

    /**
     * @return void
     */
    private function dumpAutoload()
    {
        $localComposerFile = base_path('composer.phar');
        if( file_exists( $localComposerFile ) ) {
            echo shell_exec("php {$localComposerFile} dump-autoload");
            $this->info("...autoload dumped!");
        } else {
            $this->info("Couldn't find local composer file, please run dump-autoload via composer");
        }
    }

    /**
     * @return void
     */
    private function laravelRename()
    {
        $this->call( "app:name",  ["name" => "{$this->camelCasedVendor}\\{$this->camelCasedModule}"]);
    }

    /**
     * @return void
     */
    private function moduleRename()
    {
        try { $this->renamePublicFolders(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->renameDatabaseFolders(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updateRegistrationFile(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updateReferences(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updateGulpFile(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updateComposerFile(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updatePackagesFile(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
        try { $this->updateDotGitIgnoreFile(); } catch( Exception $e ) { $this->info( $e->getMessage() ); }
    }

    /**
     * @return void
     */
    private function renameDatabaseFolders()
    {
        $this->renameMigrationFolders();
        $this->renameSeedsFolders();
    }

    /**
     * @return void
     */
    private function updateReferences()
    {
        // Application files
        $files = $this->findAllFiles( app_path() );
        foreach( $files as $file ) {
            $contents = preg_replace( "/([\/']){$this->currentVendor}([\/\.]){$this->currentModule}([\/']|(?:::))/", "$1{$this->vendor}$2{$this->module}$3", file_get_contents( $file ) );
            $contents = preg_replace(
                "#(['\"]){$this->currentCamelCasedVendor}(\\\+){$this->currentCamelCasedModule}(\\\+)#",
                "$1{$this->camelCasedVendor}$2{$this->camelCasedModule}$3",
                $contents
            );
            file_put_contents( $file, $contents );
        }
        // Resource views
        $files = $this->findAllFiles( base_path('resources/views') );
        foreach( $files as $file ) {
            $contents = preg_replace( "/([\/']){$this->currentVendor}([\/\.]){$this->currentModule}([\/']|(?:::))/", "$1{$this->vendor}$2{$this->module}$3", file_get_contents( $file ) );
            file_put_contents( $file, $contents );
        }
    }

    /**
     * @return void
     */
    private function renameMigrationFolders()
    {
        $this->copyOrRename( 'database/migrations/vendor' );
    }

    /**
     * @return void
     */
    private function renameSeedsFolders()
    {
        $this->copyOrRename( 'database/seeds/vendor' );
    }

    /**
     * @return void
     */
    private function renamePublicFolders()
    {
        $this->copyOrRename( 'public/package' );
    }

    /**
     * @return void
     */
    private function updatePackagesFile()
    {
        $file = base_path('config/packages.json');
        if( ! file_exists( $file ) ) return;
        $contents = file_get_contents( $file );
        $newContents = str_replace( "{$this->currentCamelCasedVendor}\\\\{$this->currentCamelCasedModule}", "{$this->camelCasedVendor}\\\\{$this->camelCasedModule}", $contents );
        file_put_contents( $file, $newContents );
    }

    /**
     * @return void
     */
    private function updateComposerFile()
    {
        $file = base_path('composer.json');
        if( ! file_exists( $file ) ) return;
        $contents = file_get_contents( $file );
        $newContents = str_replace( "{$this->currentCamelCasedVendor}\\\\{$this->currentCamelCasedModule}", "{$this->camelCasedVendor}\\\\{$this->camelCasedModule}", $contents );
        $newContents = str_replace( "\"{$this->currentVendor}/{$this->currentModule}\"", "\"{$this->vendor}/{$this->module}\"", $newContents );
        file_put_contents( $file, $newContents );
    }

    /**
     * @return void
     */
    private function updateRegistrationFile()
    {
        $file = base_path('EnsphereCore/ensphere-registration.json');
        if( ! file_exists( $file ) ) return;
        $contents = file_get_contents( $file );
        $newContents = str_replace( "{$this->currentCamelCasedVendor}\\\\{$this->currentCamelCasedModule}", "{$this->camelCasedVendor}\\\\{$this->camelCasedModule}", $contents );
        file_put_contents( $file, $newContents );
    }

    /**
     * @return void
     */
    private function updateGulpFile()
    {
        $file = base_path('gulpfile.js');
        if( ! file_exists( $file ) ) return;
        $contents = file_get_contents( $file );
        $newContents = str_replace( "public/package/{$this->currentVendor}/{$this->currentModule}/", "public/package/{$this->vendor}/{$this->module}/", $contents );
        file_put_contents( $file, $newContents );
    }

    /**
     * @return void
     */
    private function updateDotGitIgnoreFile()
    {
        $file = base_path('.gitignore');
        if( ! file_exists( $file ) ) return;
        $contents = explode( "\n", file_get_contents( $file ) );
        foreach( $contents as $key => $line ) {
            $line = trim( $line );
            $line = preg_replace( "#^(.*){$this->currentVendor}/{$this->currentModule}(.*)$#is", "$1{$this->vendor}/{$this->module}$2", $line );
            $contents[$key] = preg_replace( "#^!/public/package/{$this->currentVendor}/$#is", "!/public/package/{$this->vendor}/", $line );
        }
        $contents = implode( "\n", $contents );
        file_put_contents( $file, $contents );
    }

    /**
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        if ( ! file_exists( $dir ) ) return true;
        if ( ! is_dir( $dir ) ) return unlink( $dir );
        foreach ( scandir( $dir ) as $item ) {
            if ( $item == '.' || $item == '..' ) continue;
            if ( ! $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item ) ) return false;
        }
        return rmdir( $dir );
    }

    /**
     * @param $pathPrefix
     */
    public function copyOrRename( $pathPrefix )
    {
        if( $this->currentVendor !== $this->vendor ) {
            $newDir = base_path( "{$pathPrefix}/{$this->vendor}" );
            if( ! file_exists( $newDir ) ) mkdir( $newDir, 0755 );
            $this->copy(
                base_path( "{$pathPrefix}/{$this->currentVendor}/{$this->currentModule}" ),
                base_path("{$pathPrefix}/{$this->vendor}/{$this->module}")
            );
        } else {
            rename( base_path( "{$pathPrefix}/{$this->currentVendor}/{$this->currentModule}" ), base_path("{$pathPrefix}/{$this->currentVendor}/{$this->module}" ) );
            rename( base_path( "{$pathPrefix}/{$this->currentVendor}"), base_path("{$pathPrefix}/{$this->vendor}" ) );
        }
        $this->deleteDirectory( base_path( "{$pathPrefix}/{$this->currentVendor}/{$this->currentModule}" ) );
        if( !(new \FilesystemIterator( base_path( "{$pathPrefix}/{$this->currentVendor}" ) ) )->valid() ) {
            rmdir( base_path( "{$pathPrefix}/{$this->currentVendor}" ) );
        }
    }

    /**
     * @param $dir
     * @return array
     */
    private function findAllFiles( $dir )
    {
        $dir = rtrim( $dir, '/' );
        $root = scandir( $dir );
        foreach( $root as $value ) {
            if( $value === '.' || $value === '..' ) continue;
            if( is_file( "{$dir}/{$value}" ) ) {
                $result[] = "{$dir}/{$value}";
                continue;
            }
            foreach( $this->findAllFiles( "{$dir}/{$value}" ) as $value ) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * @param $source
     * @param $destination
     */
    private function copy( $source, $destination )
    {
        $delete = [];
        $source = rtrim( $source, '/' ) . '/';
        $destination = rtrim( $destination, '/' ) . '/';
        $files = $this->findAllFiles( $source );
        foreach ( $files as $file ) {
            if ( in_array( $file, array( ".", ".." ) ) ) continue;
            $file = str_replace( $source, '', $file );
            if( ! file_exists( $destination . dirname( $file ) ) ) {
                mkdir( $destination . dirname( $file ), 0755, true);
            }
            if ( @copy( $source . $file, $destination . $file ) ) {
                $delete[] = $source . $file;
            }
        }
        foreach ( $delete as $file ) {
            unlink( $file );
        }
    }

}
