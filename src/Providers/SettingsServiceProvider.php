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

use OWC\ZGW\ApiClientManager;
use OWC\ZGW\WordPress\ClientProvider;
use OWC\ZGW\WordPress\SettingsProvider;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Controllers\SettingsController;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Vendor_Prefixed\DI\NotFoundException;

/**
 * Register settings service provider.
 *
 * @since 1.0.0
 */
class SettingsServiceProvider extends ServiceProvider
{
	private SettingsController $controller;
	protected ContainerResolver $container;
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->controller = new SettingsController();
		$this->container  = ContainerResolver::make();
		$this->logger     = $this->container->get( 'logger.zgw' );
	}

	/**
	 * @inheritDoc
	 */
	public function register(): void
	{
		$manager = new ApiClientManager();
		$manager->container()->get( SettingsProvider::class )->register();
		$manager->container()->get( ClientProvider::class )->register();

		add_action(
			'init',
			function () use ( $manager ) {
				$this->overwrite_client_http_options_in_container( $manager );
			},
			11
		);

		add_action( 'admin_menu', $this->register_settings_page( ... ) );
		add_action( 'admin_init', $this->register_settings_options( ... ) );
	}

	/**
	 * Overwrite client HTTP options in the container based on the ZGW configuration settings.
	 *
	 * @since 1.7.0
	 */
	private function overwrite_client_http_options_in_container( ApiClientManager $manager ): void
	{
		$selected_suppliers = $this->container->get( 'zgw.site_options' )->client_request_timeout_option_suppliers();
		$suppliers          = $this->container->get( 'suppliers' );

		if ( ! is_array( $selected_suppliers ) || ! is_array( $suppliers ) ) {
			return;
		}

		$zgw_settings       = (array) get_option( 'zgw_api_settings' );
		$configured_clients = $zgw_settings['zgw-api-configured-clients'] ?? array();

		foreach ( $selected_suppliers as $supplier ) {
			$found_supplier = $suppliers[ $supplier ] ?? null;

			if ( ! is_string( $found_supplier ) || '' === trim( $found_supplier ) ) {
				continue;
			}

			$client_supplier_class = sprintf( 'OWC\\ZGW\\Clients\\%s\\Client', trim( $found_supplier ) );

			if ( ! class_exists( $client_supplier_class ) ) {
				$this->logger->error( sprintf( 'Could not find client class %s for supplier %s', $client_supplier_class, $supplier ) );

				continue;
			}

			// Resolve the user-configured client name for this supplier type.
			// ApiClientManager stores credentials/endpoints by the configured name, not the type key.
			$client_name = '';

			foreach ( $configured_clients as $client_config ) {
				if ( ( $client_config['client_type'] ?? '' ) === $supplier ) {
					$client_name = trim( $client_config['name'] ?? '' );

					break;
				}
			}

			if ( '' === $client_name ) {
				continue;
			}

			try {
				$credentials     = $manager->container()->get( $client_name . 'credentials' );
				$endpoints       = $manager->container()->get( $client_name . 'endpoints' );
				$supplier_client = $manager->container()->make( $client_supplier_class, compact( 'credentials', 'endpoints' ) );
				$shared_client   = $supplier_client->getRequestClient();
				$cloned_client   = clone $shared_client;
				$cloned_options  = $shared_client->getRequestOptions()->clone();

				$cloned_options->set( 'timeout', $this->container->get( 'zgw.site_options' )->client_request_timeout_option() );
				$cloned_client->setRequestOptions( $cloned_options );
				$supplier_client->setRequestClient( $cloned_client );

				$manager->container()->set(
					$client_supplier_class,
					function () use ( $supplier_client ) {
						return $supplier_client;
					}
				);
			} catch ( NotFoundException $e ) {
				$this->logger->error( sprintf( 'Could not overwrite client request options for supplier %s: %s', $supplier, $e->getMessage() ) );

				continue;
			}
		}
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

		/**
		 * @since 1.6.0
		 */
		add_settings_section(
			'owc_gf_zgw_section_delay_after_zaak_creation',
			__( 'Vertraging na zaakcreatie', 'owc-gravityforms-zgw' ),
			$this->controller->section_description_delay_after_zaak_creation( ... ),
			'owc-gf-zgw'
		);

		/**
		 * @since 1.6.0
		 */
		add_settings_field(
			'owc_zgw_delay_after_zaak_creation_seconds',
			__( 'Vertraging na zaakcreatie (seconden)', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_delay_after_zaak_creation',
			array( 'settings_field_id' => 'owc_zgw_delay_after_zaak_creation_seconds' )
		);

		/**
		 * @since 1.6.0
		 */
		add_settings_field(
			'owc_zgw_delay_after_zaak_creation_suppliers',
			__( 'Pas vertraging toe op leverancier(s)', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_delay_after_zaak_creation',
			array( 'settings_field_id' => 'owc_zgw_delay_after_zaak_creation_suppliers' )
		);

		/**
		 * @since 1.7.0
		 */
		add_settings_section(
			'owc_gf_zgw_section_client_request_timeout',
			__( 'Client request timeout', 'owc-gravityforms-zgw' ),
			$this->controller->description_client_request_timeout_option( ... ),
			'owc-gf-zgw'
		);

		/**
		 * @since 1.7.0
		 */
		add_settings_field(
			'owc_zgw_client_request_timeout_option',
			__( 'Request timeout (seconden)', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_client_request_timeout',
			array( 'settings_field_id' => 'owc_zgw_client_request_timeout_option' )
		);

		/**
		 * @since 1.7.0
		 */
		add_settings_field(
			'owc_zgw_client_request_timeout_option_suppliers',
			__( 'Pas aangepaste timeout toe op leverancier(s)', 'owc-gravityforms-zgw' ),
			$this->controller->section_fields_render( ... ),
			'owc-gf-zgw',
			'owc_gf_zgw_section_client_request_timeout',
			array( 'settings_field_id' => 'owc_zgw_client_request_timeout_option_suppliers' )
		);
	}
}
