<?php

declare(strict_types=1);

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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add prefix for the given string.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_prefix' ) ) {
	function owc_gravityforms_zgw_prefix( $name ): string
	{
		return 'owc-gravityforms-zgw-' . $name;
	}
}

/**
 * Generates a full plugin URL by appending the given path to the base plugin URL.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_url' ) ) {
	function owc_gravityforms_zgw_url( string $path ): string
	{
		return OWC_GRAVITYFORMS_ZGW_PLUGIN_URL . $path;
	}
}

/**
 * Generates a full asset URL by appending the given path to the plugin's asset directory.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
if ( ! function_exists( 'owc_gravityforms_zgw_asset_url' ) ) {
	function owc_gravityforms_zgw_asset_url( string $path ): string
	{
		return owc_gravityforms_zgw_url( 'dist/' . $path );
	}
}

/**
 * Render a view file.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
function owc_gravityforms_zgw_render_view( string $file_path, $data = array() ): mixed
{
	$full_path = OWC_GRAVITYFORMS_ZGW_DIR_PATH . 'src/Views/' . $file_path . '.php';

	if ( ! file_exists( $full_path ) ) {
		return '';
	}

	// Manually extract variables from the $data array
	foreach ( $data as $key => $value ) {
		${$key} = $value;
	}

	return require $full_path;
}

/**
 * Check if the current environment is a development environment.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
function owc_gravityforms_zgw_env_is_dev(): bool
{
	$env = wp_get_environment_type();

	return str_contains( $env, 'dev' ) || str_contains( $env, 'local' );
}

/**
 * Check if the current environment is a production environment.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.13.1
 */
function owc_gravityforms_zgw_env_is_prod(): bool
{
	return wp_get_environment_type() === 'production';
}
