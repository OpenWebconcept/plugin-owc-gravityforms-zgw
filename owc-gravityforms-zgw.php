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
 * Description:       Make a connection between ZGW and GravityForms
 * Version:           1.0.0
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/LICENSE.txt
 * Text Domain:       owc-gravityforms-gw
 * Domain Path:       /languages
 */

declare ( strict_types = 1 );

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
    exit;
}

const OWC_GRAVITYFORMS_ZGW_VERSION = '1.0.0';
const OWC_GRAVITYFORMS_ZGW_REQUIRED_WP_VERSION = '6.7';
const OWC_GRAVITYFORMS_ZGW_FILE = __FILE__;

define( 'OWC_GRAVITYFORMS_ZGW_DIR_PATH', plugin_dir_path( OWC_GRAVITYFORMS_ZGW_FILE ) );
define( 'OWC_GRAVITYFORMS_ZGW_PLUGIN_URL', plugins_url( '/', OWC_GRAVITYFORMS_ZGW_FILE ) );

$autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/Bootstrap.php';

add_action(
    'plugins_loaded',
    function () {
        $init = new OWCGravityFormsZGW\Bootstrap();
    }
);