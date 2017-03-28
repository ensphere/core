<?php

namespace EnsphereCore\Commands\Ensphere\Extend;

use Illuminate\Console\Command as IlluminateCommand;
use ReflectionClass;
use EnsphereCore\Commands\Ensphere\Traits\Module;

class Command extends IlluminateCommand
{

    use Module;

    protected $signature = 'ensphere:extend {class}';

    protected $description = 'Extends a class to the local application';

    protected $createdFile = false;

    /**
     * Extend constructor.
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
        $class = $this->argument( 'class' );
        if( ! class_exists( $class ) ) return $this->error( "Could not find class '{$class}'" );
        $reflection = new ReflectionClass( $class );

        $blueprints = array_keys( $reflection->getInterfaces() );
        $extendedClassName = '';
        if( $extendedClass = $reflection->getParentClass() ) {
            $extendedClassName = $extendedClass->getName();
        }
        if( ! $moduleName = $this->getModuleName( $class ) ) {
            return $this->error( "Could not find module name" );
        }
        $this->createOverridesFolder( $moduleName );
        $filePath = $this->createFoldersInOverridesFor( $moduleName, $class );
        $this->createOverridingFileContents( $filePath, $moduleName, $class, $extendedClassName, $blueprints );
    }

    /**
     * @param $class
     * @return bool
     */
    private function getModuleName( $class )
    {
        if( preg_match( "#^Purposemedia\\\([^\\\]+)#", $class, $matches ) ) {
            return $matches[1];
        }
        return false;
    }

    /**
     * @param $moduleName
     */
    private function createOverridesFolder( $moduleName )
    {
        if( ! file_exists( app_path( 'Extending' ) ) ) mkdir( app_path( 'Extending' ) );
        if( ! file_exists( app_path( 'Extending/' . $moduleName ) ) ) mkdir( app_path( 'Extending/' . $moduleName ) );
    }

    /**
     * @param $moduleName
     * @param $class
     * @return string
     */
    private function createFoldersInOverridesFor( $moduleName, $class )
    {
        $extendsPath = app_path( "Extending/{$moduleName}/" );
        $pathSegments = explode( '\\', $class );
        $file = $pathSegments[count($pathSegments)-1];
        unset( $pathSegments[count($pathSegments)-1], $pathSegments[0], $pathSegments[1] );
        foreach( $pathSegments as $segment ) {
            $extendsPath .= "{$segment}/";
            if( ! file_exists( $extendsPath ) ) mkdir( $extendsPath );
        }
        if( ! file_exists( "{$extendsPath}{$file}.php" ) ) {
            $this->createdFile = true;
            touch( "{$extendsPath}{$file}.php" );
        }
        return "{$extendsPath}{$file}.php";
    }

    /**
     * @param $filePath
     * @param $moduleName
     * @param $class
     * @param $extendedClassName
     * @param $blueprints
     */
    private function createOverridingFileContents( $filePath, $moduleName, $class, $extendedClassName, $blueprints )
    {
        if( ! $this->createdFile ) return $this->error( "File already exists, abortting!" );
        $applicationData = $this->getCurrentVendorAndModuleName();
        $pathSegments = explode( '\\', $class );
        unset( $pathSegments[count($pathSegments)-1], $pathSegments[0], $pathSegments[1] );
        $subNamespace = implode( '\\', $pathSegments );
        $className = pathinfo( $filePath ,PATHINFO_FILENAME );
        $fileNamespace = $applicationData['camelCasedVendor'] . '\\' . $applicationData['camelCasedModule'] . '\Extending\\' . $moduleName . '\\' . $subNamespace;

        $implements = '';
        $blueprint = '';
        if( count( $blueprints ) == 1 ) {
            $blueprint = $blueprints[0];
            if( preg_match( "#Blueprints\\\([^\\\]+)#", $blueprint, $match ) ) {
                $blueprint = "use {$blueprint} as Original" . $match[1] . 'Blueprint;';
                $implements = "implements Original" . $match[1] . 'Blueprint';
                $this->addToApplicationServiceProvider( $blueprints[0], $fileNamespace . '\\' . $className );
            }
        }

        $str = '<?php

namespace ' . $fileNamespace . ';

use ' . $class . ' as Original' . $className . ';
' . $blueprint . '

class ' . $className . ' extends Original' . $className . ' ' . $implements . ' {

}
        ';
        file_put_contents( $filePath, $str );
        $this->info("{$fileNamespace} created");
    }

    /**
     * @param $blueprint
     * @param $className
     */
    private function addToApplicationServiceProvider( $blueprint, $className )
    {
        if( $appServiceProvider = @file_get_contents( app_path( 'Providers/AppServiceProvider.php' ) ) ) {
            if( preg_match( '#\$contracts\s=\sService::contracts\(\[[^\]]+#s', $appServiceProvider, $matches ) ) {
                $dfd = rtrim( rtrim( $matches[0] ), ',' ) . ",\n\t\t\t\t" . '"' . addslashes( $blueprint ) . '" => "' . addslashes( $className ) . '"' . "\n\t\t\t";
                $appServiceProvider = str_replace( "/** THESE ARE APPLICATION CONTRACTS. */,", "/** THESE ARE APPLICATION CONTRACTS. */", str_replace( $matches[0], $dfd, $appServiceProvider ) );
                file_put_contents( app_path( 'Providers/AppServiceProvider.php' ), $appServiceProvider );
            }
        }
    }
}
