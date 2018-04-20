<?php


namespace EnsphereCore\Commands\Ensphere\Traits;


trait ModelExtend
{

    /**
     * Returns the last created item from life cycle
     *
     * @var null
     */
    public static $lastCreated = null;

    /**
     * Returns the last updated item from life cycle
     *
     * @var null
     */
    public static $lastUpdated = null;

    /**
     * @return mixed
     */
    public static function lastCreated()
    {
        return self::$lastCreated;
    }

    /**
     * @return mixed
     */
    public static function lastUpdated()
    {
        return self::$lastUpdated;
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool|int
     */
    public function update( array $attributes = [], array $options = [] )
    {
        if( ! $this->exists ) {
            return false;
        }
        return self::$lastUpdated = $this->fill( $attributes )->save( $options );
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function create( array $attributes = [] )
    {
        $model = new static( $attributes );
        $model->save();
        return self::$lastCreated = $model;
    }

}
