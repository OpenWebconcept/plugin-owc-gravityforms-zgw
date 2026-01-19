<?php

declare(strict_types=1);

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

		add_action( 'cmb2_admin_init', $this->register_settings_page_cmb2( ... ) );
		add_action( 'admin_menu', $this->register_settings_page( ... ) );
		add_action( 'admin_init', $this->register_settings_options( ... ) );
	}

	public function register_settings_page_cmb2(): void
	{
		$options = new_cmb2_box(
			array(
				'id'           => 'zgw-verzoeken-settings',
				'title'        => 'ZGW Verzoeken instellingen',
				'object_types' => array( 'options-page' ),

				'option_key'   => 'zgw_verzoeken_settings',
				'parent_slug'  => 'options-general.php',
				'capability'   => 'manage_options',
			)
		);

		$clients = $options->add_field(
			array(
				'id'          => 'zgw-verzoeken-configured-clients',
				'type'        => 'group',
				'description' => 'Configureer Verzoeken registers',
				'options'     => array(
					'group_title'   => 'Verzoeken Register {#}',
					'add_button'    => 'Voeg nog een register toe',
					'remove_button' => 'Verwijder register',
				),
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Objecttypen endpoint',
				'attributes' => array( 'placeholder' => 'https://website.nl' ),
				'protocols'  => array( 'https', 'http' ),
				'id'         => 'zgw_objecttypes_endpoint',
				'type'       => 'text_url',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Objecten endpoint',
				'attributes' => array( 'placeholder' => 'https://website.nl' ),
				'protocols'  => array( 'https', 'http' ),
				'id'         => 'zgw_objects_endpoint',
				'type'       => 'text_url',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Token',
				'id'         => 'zgw_verzoeken_token',
				'type'       => 'text',
				'attributes' => array( 'type' => 'password' ),
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Informatieobjecttypen endpoint',
				'attributes' => array( 'placeholder' => 'https://website.nl' ),
				'protocols'  => array( 'https', 'http' ),
				'id'         => 'zgw_informationobjecttypes_endpoint',
				'type'       => 'text_url',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Object informatieobjecten endpoint',
				'attributes' => array( 'placeholder' => 'https://website.nl' ),
				'protocols'  => array( 'https', 'http' ),
				'id'         => 'zgw_object_information_objects_endpoint',
				'type'       => 'text_url',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name' => 'Client identifier',
				'id'   => 'zgw_catalog_client_id',
				'type' => 'text',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Client secret',
				'id'         => 'zgw_catalog_client_secret',
				'type'       => 'text',
				'attributes' => array( 'type' => 'password' ),
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Informatieobjecten endpoint',
				'attributes' => array( 'placeholder' => 'https://website.nl' ),
				'protocols'  => array( 'https', 'http' ),
				'id'         => 'zgw_informationobject_endpoint',
				'type'       => 'text_url',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name' => 'Client identifier',
				'id'   => 'zgw_documents_client_id',
				'type' => 'text',
			)
		);

		$options->add_group_field(
			$clients,
			array(
				'name'       => 'Client secret',
				'id'         => 'zgw_documents_client_secret',
				'type'       => 'text',
				'attributes' => array( 'type' => 'password' ),
			)
		);
	}

	/**
	 * Add a settings page to the wp-admin.
	 *
	 * @since 1.1.0
	 */
	public function register_settings_page(): void
	{
		add_options_page(
			__( 'OWC | GravityForms ZGW instellingen', 'owc-gravityforms-zgw' ),
			__( 'OWC | GravityForms ZGW instellingen', 'owc-gravityforms-zgw' ),
			'manage_options',
			OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX,
			$this->controller->render_page( ... )
		);
	}

	/**
	 * Initialize the options for the settings page.
	 *
	 * @since 1.1.0
	 */
	public function register_settings_options(): void
	{
		register_setting(
			'owc_gf_zgw_options_group',
			OWC_GRAVITYFORMS_ZGW_SITE_OPTION_NAME,
			array(
				'sanitize_callback' => $this->controller->sanitize_validate_plugin_options_settings( ... ),
			)
		);

		add_settings_section(
			'owc_gf_zgw_section_transactions_overview',
			__( 'Transactie overzicht', 'owc-gravityforms-zgw' ),
			$this->controller->section_description_transactions_overview( ... ),
			'owc-gf-zgw'
		);

		add_settings_field(
			'owc_gf_zgw_transaction_user_roles',
			__( 'Gebruikersrollen', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_transactions_overview',
			array( 'settings_field_id' => 'owc_gf_zgw_transaction_user_roles' )
		);

		add_settings_section(
			'owc_gf_zgw_section_transactions_report',
			__( 'Transactie rapport', 'owc-gravityforms-zgw' ),
			$this->controller->section_description_transactions_report( ... ),
			'owc-gf-zgw'
		);

		add_settings_field(
			'owc_zgw_transactions_report_recipient_email',
			__( 'Verzenden naar', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_transactions_report',
			array( 'settings_field_id' => 'owc_zgw_transactions_report_recipient_email' )
		);
	}
}
