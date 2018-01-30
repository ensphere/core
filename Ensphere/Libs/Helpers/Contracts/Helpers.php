<?php

namespace EnsphereCore\Libs\Helpers\Contracts;

use EnsphereCore\Libs\Helpers\Contracts\Blueprints\HelpersBlueprint;
use EnsphereCore\Commands\Ensphere\Traits\Module;
use Illuminate\Database\Eloquent\Model;
use Purposemedia\FrontContainer\Models\Tracker;
use ReflectionClass;
use Schema;

class Helpers implements HelpersBlueprint
{

    use Module;

    protected $module;

    /**
     * Helpers constructor.
     */
    public function __construct()
    {
        $this->module = $this->getCurrentVendorAndModuleName();
    }

    /**
     * @param string $basename
     * @return string
     */
    public function publicUrl( $basename = '' )
    {
        return '/package/' . $this->module['vendor'] . '/' . $this->module['module'] . "/{$basename}";
    }

    /**
     * @param string $basename
     * @return mixed
     */
    public function publicImg( $basename = '' )
    {
        return public_url( "images/{$basename}" );
    }

    public function displaySuccessMessage()
    {
        $message = session('success');
        if( $message ) {
            echo '
            <div class="ui success message">
                <div class="header">Success!</div>
                <p>' . $message . '</p>
            </div>';
        }
    }

    /**
     * @param $errors
     */
    public function displayErrorMessages( $errors )
    {

        if( ! $errors->isEmpty() ) {
            echo '
            <div class="ui error message">
                <div class="header">
                    There were some errors with your submission
                </div>
                <ul class="list">';
                    foreach( $errors->all() as $error ) {
                        echo "<li>{$error}</li>";
                    }
                    echo '
                </ul>
            </div>';
        }
    }

    /**
     * @param $model
     * @return string
     */
    public function baseModelName( $model )
    {
        /** ALL front end models should extend the Tracker model and the admin should be the Eloquent model */
        $baseModels = [ Tracker::class, Model::class ];
        $modelName = get_class( $model );
        $reflection = new ReflectionClass( $model );
        if( $parent = $reflection->getParentClass() ) {
            $parentName = $parent->getName();
            if( in_array( $parentName, $baseModels ) ) return $modelName;
            return $this->baseModelName( ( new $parentName ) );
        }
        return $modelName;
    }

    /**
     * @param $table
     * @param $column
     * @return mixed
     */
    public function hasIndex( $table, $column )
    {
        $conn = Schema::getConnection();
        $dbSchemaManager = $conn->getDoctrineSchemaManager();
        $doctrineTable = $dbSchemaManager->listTableDetails( $table );
        return $doctrineTable->hasIndex( "{$table}_{$column}_index" );
    }

}
