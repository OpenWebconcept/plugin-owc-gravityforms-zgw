<?php
/**
 * Event used to populate form settings of all configured suppliers.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\WPCron\Events;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;
use OWCGravityFormsZGW\LoggerZGW;
use OWC\ZGW\Contracts\AbstractClient;
use function OWC\ZGW\apiClient;

/**
 * Event used to populate form settings of all configured suppliers.
 *
 * @since 1.1.0
 */
class PopulateFormSettings
{
	protected ContainerResolver $container;
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->container = ContainerResolver::make();
		$this->logger    = ContainerResolver::make()->get( 'logger.zgw' );
	}

	public function init(): void
	{
		$clients   = (array) get_option( 'zgw_api_settings' );
		$clients   = $clients['zgw-api-configured-clients'] ?? array();
		$suppliers = ContainerResolver::make()->get( 'zgw.api-configured-clients' );

		if ( ! is_array( $suppliers ) || array() === $suppliers ) {
			return;
		}

		foreach ( $suppliers as $supplier ) {
			$this->handle_form_settings_supplier( $supplier );
		}
	}

	private function handle_form_settings_supplier(array $supplier ): void
	{
		if ( ! isset( $supplier['name'] ) ) {
			$this->logger->error( 'Supplier name is missing in supplier configuration.' );

			return;
		}

		try {
			$api_client = apiClient( $supplier['name'] );

			if ( ! $api_client instanceof AbstractClient ) {
				throw new Exception( 'API client could not be instantiated.' );
			}
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Error initializing API client for supplier %s: %s', $supplier['name'], $e->getMessage() ) );

			return;
		}

		( new ZaaktypenAdapter( client: $api_client, supplier_name: $supplier['name'], is_cron: true ) )->handle();
		( new InformatieobjecttypeAdapter( client: $api_client, supplier_name: $supplier['name'], is_cron: true ) )->handle();
	}
}
