<?php

use EnsphereCore\Libs\Helpers\Contracts\Blueprints\HelpersBlueprint;

/**
 * @param string $basename
 * @return mixed
 */
function public_url( $basename = '' )
{
    return app()->make( HelpersBlueprint::class )->publicUrl( $basename );
}

/**
 * @param string $basename
 * @return mixed
 */
function public_img( $basename = '' )
{
    return app()->make( HelpersBlueprint::class )->publicImg( $basename );
}

/**
 * @return mixed
 */
function display_success_message()
{
    return app()->make( HelpersBlueprint::class )->displaySuccessMessage();
}

/**
 * @param $errors
 * @return mixed
 */
function display_error_messages( $errors )
{
    return app()->make( HelpersBlueprint::class )->displayErrorMessages( $errors );
}
