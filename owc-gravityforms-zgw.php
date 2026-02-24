<?php
/**
 * OWC GravityForms ZGW.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 *
 * Plugin Name:       OWC | GravityForms ZGW
 * Plugin URI:        https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw
 * Description:       Koppelt GravityForms met ZGW.
 * Version:           1.5.0
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/LICENSE.txt
 * Text Domain:       owc-gravityforms-zgw
 * Domain Path:       /languages
 * Requires Plugins:  action-scheduler, gravityforms, cmb2
 */

declare (strict_types = 1);

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OWC_GRAVITYFORMS_ZGW_VERSION             = '1.5.0';
const OWC_GRAVITYFORMS_ZGW_REQUIRED_WP_VERSION = '6.7';
const OWC_GRAVITYFORMS_ZGW_FILE                = __FILE__;

define( 'OWC_GRAVITYFORMS_ZGW_DIR_PATH', plugin_dir_path( OWC_GRAVITYFORMS_ZGW_FILE ) );
define( 'OWC_GRAVITYFORMS_ZGW_PLUGIN_URL', plugins_url( '/', OWC_GRAVITYFORMS_ZGW_FILE ) );
const OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX                   = 'owc-gf-zgw';
const OWC_GRAVITYFORMS_ZGW_ADD_ON_SETTINGS_PREFIX            = 'owc-gf-zgw-add-on';
const OWC_GRAVITYFORMS_ZGW_PLUGIN_SLUG                       = 'owc-gravityforms-zgw';
const OWC_GRAVITYFORMS_ZGW_LOGGER_DEFAULT_MAX_FILES          = 7;
const OWC_GRAVITYFORMS_ZGW_SITE_OPTION_NAME                  = 'owc_gf_zgw_options';
const OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_ZAAK        = 'owc_gf_zgw_process_zaak_creation';
const OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_TRANSACTION = 'owc_gf_zgw_process_transaction';
const OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_GROUP            = 'owc-gravityforms-zgw';

// Require autoload if they exist.
foreach ( array( 'vendor/autoload.php', 'vendor-prefixed/autoload.php' ) as $autoload ) {
	$path = __DIR__ . '/' . $autoload;

	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/Bootstrap.php';

// Clear scheduled events on plugin deactivation.
register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'owc_zgw_transaction_report' );
		wp_clear_scheduled_hook( 'owc_zgw_transaction_cleaner' );
	}
);

add_action(
	'after_setup_theme',
	function () {
		$init = new OWCGravityFormsZGW\Bootstrap();
	}
);
