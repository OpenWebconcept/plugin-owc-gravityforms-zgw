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
	function owc_gravityforms_zgw_prefix($name ): string
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
	function owc_gravityforms_zgw_url(string $path ): string
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
	function owc_gravityforms_zgw_asset_url(string $path ): string
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
function owc_gravityforms_zgw_render_view(string $file_path, $data = array() )
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
 * Get the current environment type.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
function owc_gravityforms_zgw_env_type(): string {
	return defined( 'WP_ENVIRONMENT_TYPE' ) ? WP_ENVIRONMENT_TYPE : 'production';
}

/**
 * Check if the current environment is a development environment.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
function owc_gravityforms_zgw_env_is_dev(): bool {
	return strpos( owc_gravityforms_zgw_env_type(), 'dev' ) !== false;
}
