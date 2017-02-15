<?php

namespace EnsphereCore\Libs\DotEnv;

class Registrar
{

    protected $properties = [];

    private $startTag = '#ensphere-core-settings-start';

    private $endTag = '#ensphere-core-settings-end';

    private $dotEnvFileLines = null;

    /**
     * @param Property $property
     */
    public function add( Property $property )
    {
        array_push( $this->properties, $property );
    }

    public function generate()
    {
        if( ! $this->hasDotEnvFile() ) $this->createDotEnvFile();
        if( ! $this->hasCoreSettingsBlockDefined() ) $this->addCoreSettingsBlock();
        $this->addUndefinedPropertiesToDotEnvFileLines();
        $this->saveDotEnvFile();
    }

    /**
     * @return bool
     */
    private function hasDotEnvFile()
    {
        return file_exists( base_path( '.env' ) );
    }

    private function createDotEnvFile()
    {
        touch( base_path( '.env' ) );
    }

    /**
     * @return mixed
     */
    private function getDotEnvFileLines()
    {
        if( is_null( $this->dotEnvFileLines ) ) {
            $this->dotEnvFileLines = explode( "\n", file_get_contents( base_path( '.env' ) ) );
        }
        return $this->dotEnvFileLines;
    }

    /**
     * @return bool
     */
    private function hasCoreSettingsBlockDefined()
    {
        foreach( $this->getDotEnvFileLines() as $line ) {
            if( $line === $this->startTag ) return true;
        }
        return false;
    }

    private function addCoreSettingsBlock()
    {
        $this->dotEnvFileLines = array_merge( $this->dotEnvFileLines, [
            '',
            $this->startTag,
            $this->endTag
        ]);
    }

    private function addUndefinedPropertiesToDotEnvFileLines()
    {
        $toAdd = $this->getUndefinedSettings();
        $read = false;
        $newDotEnvLines = [];
        foreach( $this->dotEnvFileLines as $line ) {
            if( $line === $this->startTag ) $read = true;
            $newDotEnvLines[] = $line;
            if( $read ) {
                foreach( $toAdd as $key => $value ) {
                    $newDotEnvLines[] = "{$key}={$value}";
                }
                $read = false;
            }
        }
        $this->dotEnvFileLines = $newDotEnvLines;
    }

    /**
     * @return array
     */
    private function getUndefinedSettings()
    {
        $customDefined = [];
        $defined = [];
        $toAdd = [];
        $read = false;
        foreach( $this->dotEnvFileLines as $line ) {
            if( $line === $this->startTag ) $read = true;
            if( $line === $this->endTag ) $read = false;
            if( $read ) {
                if( $keyValuePair = $this->getKeyValuePair( $line ) ) {
                    $defined[key( $keyValuePair )] = $keyValuePair[key( $keyValuePair )];
                }
            } else {
                if( $keyValuePair = $this->getKeyValuePair( $line ) ) {
                    $customDefined[key( $keyValuePair )] = $keyValuePair[key( $keyValuePair )];
                }
            }
        }
        foreach( $this->properties as $propertyObj ) {
            if( ! isset( $defined[ $propertyObj->getKey() ] ) ) {
                if( ! isset( $customDefined[ $propertyObj->getKey() ] ) ) {
                    $toAdd[ $propertyObj->getKey() ] = $propertyObj->getDefaultValue();
                } else {
                    $toAdd[ $propertyObj->getKey() ] = $customDefined[ $propertyObj->getKey() ];
                }
            }
        }
        $this->removeDuplicates( $toAdd );
        return $toAdd;
    }

    /**
     * @param $toAdd
     */
    private function removeDuplicates( $toAdd )
    {
        foreach( $this->dotEnvFileLines as $key => $line ) {
            if( $keyValuePair = $this->getKeyValuePair( $line ) ) {
                if( isset( $toAdd[key( $keyValuePair )] ) ) {
                    unset( $this->dotEnvFileLines[$key] );
                }
            }
        }
    }

    private function saveDotEnvFile()
    {
        file_put_contents( base_path( '.env' ), implode( "\n", $this->dotEnvFileLines ) );
    }

    /**
     * @param $line
     * @return array|bool
     */
    private function getKeyValuePair( $line )
    {
        if( ! preg_match( "#^([A-Z_]+)=([^\s]+)$#", $line, $match ) ) {
            if( ! preg_match( "#^([A-Z_]+)=\"([^\"])\"$#", $line, $match ) ) return false;
        }
        return [ $match[1] => $match[2] ];
    }

    public function showInfo( $command )
    {
        $command->info( "" );
        if( empty( $this->properties ) ) {
            $command->error( "No properties have been assigned" );
        }
        foreach( $this->properties as $property ) {
            $command->line( '<fg=yellow;>' . $property->getKey() . '</>' );
            $command->line( "\t<fg=blue;>Default:</> <fg=green;>" . $property->getDefaultValue() . '</>');
            $command->line( "\t<fg=blue;>Description:</> <fg=green;>" . $property->getDescription() . '</>');
            $command->line( "\t<fg=blue;>Owner:</> <fg=green;>" . $property->getOwner() . '</>');
            $acceptedValues = $property->getAcceptedValues();
            if( ! empty( $acceptedValues ) ) {
                $command->line( "\t<fg=blue;>Accepted Values:</>" );
                $command->line( "\t\t<fg=green;>'" . implode( "'\n\t\t'", $acceptedValues ) . "'</>");
            }
        }
        $command->info( "" );
    }

}
