<?php
/**
 * Register settings service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Controllers\SettingsController;
use OWC\ZGW\ApiClientManager;
use OWC\ZGW\WordPress\ClientProvider;
use OWC\ZGW\WordPress\SettingsProvider;

/**
 * Register settings service provider.
 *
 * @since 1.0.0
 */
class SettingsServiceProvider extends ServiceProvider
{
	private SettingsController $controller;

	public function __construct()
	{
		$this->controller = new SettingsController();
	}

	/**
	 * @inheritDoc
	 */
	public function register(): void
	{
		$manager = new ApiClientManager();
		$manager->container()->get( SettingsProvider::class )->register();
		$manager->container()->get( ClientProvider::class )->register();

		add_action( 'admin_menu', $this->register_settings_page( ... ) );
		add_action( 'admin_init', $this->register_settings_options( ... ) );
	}

	/**
	 * Add a settings page to the wp-admin.
	 *
	 * @since NEXT
	 */
	public function register_settings_page(): void
	{
		add_options_page(
			__( 'ZGW instellingen', 'owc-gravityforms-zgw' ),
			__( 'ZGW instellingen', 'owc-gravityforms-zgw' ),
			'manage_options',
			OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX,
			$this->controller->render_page( ... )
		);
	}

	/**
	 * Initialize the options for the settings page.
	 *
	 * @since NEXT
	 */
	public function register_settings_options(): void
	{
		register_setting(
			'owc_gf_zgw_options_group',
			OWC_GRAVITYFORMS_ZGW_SITE_OPTION_NAME,
			array(
				'sanitize_callback' => $this->controller->sanitize_plugin_options_settings( ... ),
			)
		);

		add_settings_section(
			'owc_gf_zgw_section_general',
			__( 'Instellingen', 'owc-gravityforms-zgw' ),
			$this->controller->section_description_general( ... ),
			'owc-gf-zgw'
		);

		add_settings_field(
			'owc_gf_zgw_transaction_user_roles',
			__( 'Gebruikersrollen', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_general',
			array( 'settings_field_id' => 'owc_gf_zgw_transaction_user_roles' )
		);
	}
}
