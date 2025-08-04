<?php

/**
 * OWC GravityForms ZGW.
 *
 * @package OWC_GravityForms_ZGW
 *
 * @author  Yard | Digital Agency
 *
 * @since   1.0.0
 *
 * Plugin Name:       OWC | GravityForms ZGW
 * Plugin URI:        https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw
 * Description:       Koppelt GravityForms met ZGW.
 * Version:           1.0.0
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/LICENSE.txt
 * Text Domain:       owc-gravityforms-zgw
 * Domain Path:       /languages
 * Requires Plugins:  gravityforms
 */

declare (strict_types = 1);

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

const OWC_GRAVITYFORMS_ZGW_VERSION             = '1.0.0';
const OWC_GRAVITYFORMS_ZGW_REQUIRED_WP_VERSION = '6.7';
const OWC_GRAVITYFORMS_ZGW_FILE                = __FILE__;

define( 'OWC_GRAVITYFORMS_ZGW_DIR_PATH', plugin_dir_path( OWC_GRAVITYFORMS_ZGW_FILE ) );
define( 'OWC_GRAVITYFORMS_ZGW_PLUGIN_URL', plugins_url( '/', OWC_GRAVITYFORMS_ZGW_FILE ) );
const OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX        = 'owc-gf-zgw';
const OWC_GRAVITYFORMS_ZGW_ADD_ON_SETTINGS_PREFIX = 'owc-gf-zgw-add-on';
const OWC_GRAVITYFORMS_ZGW_PLUGIN_SLUG            = 'owc-gravityforms-zgw';

const OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_FAILED_SUBMISSION = 'zgw_submission_zaak_failed_message';
const OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_CREATED_ZAAK      = 'zgw_created_zaak';
const OWC_GRAVITYFORMS_ZGW_LOGGER_DEFAULT_MAX_FILES        = 7;

$autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists( $autoload )) {
	require_once $autoload;
}

require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/Bootstrap.php';

add_action(
	'after_setup_theme',
	function () {
		$init = new OWCGravityFormsZGW\Bootstrap();
	}
);
