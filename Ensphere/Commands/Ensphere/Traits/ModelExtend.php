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
        $updated = $this->fill( $attributes )->save( $options );
        self::$lastUpdated = $this;
        return $updated;
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

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save( array $options = [] )
    {
        $query = $this->newQueryWithoutScopes();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
            self::$lastUpdated = $this;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query, $options);
            self::$lastCreated = $this;
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

}
