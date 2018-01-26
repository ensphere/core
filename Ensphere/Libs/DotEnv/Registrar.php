<?php

namespace EnsphereCore\Libs\DotEnv;

class Registrar
{

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var string
     */
    private $startTag = '#ensphere-core-settings-start';

    /**
     * @var string
     */
    private $endTag = '#ensphere-core-settings-end';

    /**
     * @var null
     */
    private $dotEnvFileLines = null;

    /**
     * @param Property $property
     */
    public function add( Property $property )
    {
        array_push( $this->properties, $property );
    }

    /**
     * @return void
     */
    public function generate()
    {
        if( ! $this->hasDotEnvFile() ) $this->createDotEnvFile();

        $lines = $this->getDotEnvFileLines();
        $defined = [];
        $others = [];
        $inside = false;
        foreach( $lines as $line ) {
            $line = trim( $line );
            if( $line === $this->startTag ) {
                $inside = true; continue;
            }
            if( $line === $this->endTag ) {
                $inside = false; continue;
            }
            if( ! preg_match( '/^([^=]+)=(.+)$/', $line, $matches ) ) {
                continue;
            }
            $pair = array_map( function( $string ) {
                return trim( $string, '"' );
            }, [ $matches[1], $matches[2] ] );
            if( isset( $pair[1] ) ) {
                if( $inside ) {
                    $defined[ $pair[ 0 ] ] = $pair[ 1 ];
                } else {

                    $others[ $pair[ 0 ] ] = $pair[ 1 ];
                }
            }
        }

        foreach( $this->properties as $propertyObj ) {
            $key = $propertyObj->getKey();
            if( isset( $defined[$key] ) && isset( $others[$key] ) ) {
                // Defined in both for some reason... we'll delete the "others" one
                unset( $defined[$key] );
            }
            elseif( isset( $others[$key] ) ) {
                // Already defined... we'll move it the the correct place
                $defined[$key] = $others[$key];
                unset( $others[$key] );
            }
            elseif( ! isset( $defined[$key] ) ) {
                // Not set in the defined so we'll add the default setting in there...
                $defined[$key] = $propertyObj->getDefaultValue();
            }
        }

        $defined = $this->sortAlphabetically( $defined );
        $others = $this->sortAlphabetically( $others );

        $file = '';

        foreach( $others as $alphaBlock ) {
            $file .= "\n";
            foreach( $alphaBlock as $key => $value ) {
                $file .= "{$key}=\"{$value}\"\n";
            }
        }

        $file .= "\n" . $this->startTag . "\n";
        foreach( $defined as $alphaBlock ) {
            //$file .= "\n";
            foreach( $alphaBlock as $key => $value ) {
                $file .= "{$key}=\"{$value}\"\n";
            }
        }
        $file .= $this->endTag . "\n";

        $this->dotEnvFileLines = $file;
        $this->saveDotEnvFile();

    }

    /**
     * @param $array
     * @return array
     */
    protected function sortAlphabetically( $array )
    {
        ksort( $array );
        $chunks = [];
        foreach( $array as $key => $value ) {
            $keyLetter = strtoupper( $key )[0];
            if( ! isset( $chunks[$keyLetter] ) ) {
                $chunks[$keyLetter] = [];
            }
            $chunks[$keyLetter][$key] = $value;
        }
        return $chunks;
    }

    /**
     * @return bool
     */
    private function hasDotEnvFile()
    {
        return file_exists( base_path( '.env' ) );
    }

    /**
     * @return void
     */
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
     * @return void
     */
    private function saveDotEnvFile()
    {
        file_put_contents( base_path( '.env' ), $this->dotEnvFileLines );
    }

    /**
     * @param $command
     * @return void
     */
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
