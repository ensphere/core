<?php

use EnsphereCore\Libs\Helpers\Contracts\Blueprints\HelpersBlueprint;

/**
 * @param string $basename
 * @return mixed
 */
if( ! function_exists( 'public_url' ) ) {
    function public_url( $basename = '' )
    {
        return app()->make( HelpersBlueprint::class )->publicUrl( $basename );
    }
}

/**
 * @param string $basename
 * @return mixed
 */
if( ! function_exists( 'public_img' ) ) {
    function public_img( $basename = '' )
    {
        return app()->make( HelpersBlueprint::class )->publicImg( $basename );
    }
}

/**
 * @return mixed
 */
if( ! function_exists( 'display_success_message' ) ) {
    function display_success_message()
    {
        return app()->make( HelpersBlueprint::class )->displaySuccessMessage();
    }
}

/**
 * @param $errors
 * @return mixed
 */
if( ! function_exists( 'display_error_messages' ) ) {
    function display_error_messages( $errors )
    {
        return app()->make( HelpersBlueprint::class )->displayErrorMessages( $errors );
    }
}

/**
 * @param $model
 * @return mixed
 */
if( ! function_exists( 'base_model_name' ) ) {
    function base_model_name( $model )
    {
        return app()->make( HelpersBlueprint::class )->baseModelName( $model );
    }
}

/**
 * @param $table
 * @param $column
 * @return mixed
 */
if( ! function_exists( 'has_index' ) ) {
    function has_index( $table, $column )
    {
        return app()->make( HelpersBlueprint::class )->hasIndex( $table, $column );
    }
}

if( ! function_exists( 'routes_json_url' ) ) {
    function routes_json_url()
    {
        return app()->make( HelpersBlueprint::class )->routesJsonUrl();
    }
}
