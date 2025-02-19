<?php
/**
 * Plugin helpers.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
    exit;
}

/**
 * Add prefix for the given string.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_prefix' )) {
    function owc_gravityforms_zgw_prefix( $name ): string
    {
        return 'owc-gravityforms-zgw-' . $name;
    }
}

/**
 * Add prefix for the given string.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_url' )) {
    function owc_gravityforms_zgw_url( string $path ): string
    {
        return OWC_GRAVITYFORMS_ZGW_PLUGIN_URL . $path;
    }
}

/**
 * Add prefix for the given string.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_asset_url' )) {
    function owc_gravityforms_zgw_asset_url( string $path ): string
    {
        return owc_gravityforms_zgw_url( 'dist/' . $path );
    }
}