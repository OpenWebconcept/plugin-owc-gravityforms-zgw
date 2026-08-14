<?php

declare(strict_types=1);

/**
 * Product choices controller.
 *
 * Handles the AJAX request that live-refreshes the "product" select in the form settings
 * whenever the "zaaktype" select changes, based on the "productenOfDiensten" cached for
 * that zaaktype (see ZaaktypenAdapter::get_cached_products()). Reads only from that cache,
 * so this never triggers a live (and potentially slow) API request.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.16.0
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;

/**
 * Product choices controller.
 *
 * @since 1.16.0
 */
class ProductChoicesController
{
	/**
	 * @since 1.16.0
	 */
	public function handle(): void
	{
		if ( ! current_user_can( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'owc-gravityforms-zgw' ) ), 403 );
		}

		check_ajax_referer( 'owcgfzgw_get_producten' );

		$supplier_name       = $this->resolve_supplier_name( sanitize_text_field( wp_unslash( $_POST['supplier'] ?? '' ) ) );
		$zaaktype_identifier = sanitize_text_field( wp_unslash( $_POST['zaaktype'] ?? '' ) );

		if ( '' === $supplier_name || '' === $zaaktype_identifier ) {
			wp_send_json_success( array( 'products' => array() ) );
		}

		wp_send_json_success(
			array(
				'products' => ZaaktypenAdapter::get_cached_products( $supplier_name, $zaaktype_identifier ),
			)
		);
	}

	/**
	 * Resolves the configured supplier client name from the posted (lowercase) supplier key.
	 *
	 * @since 1.16.0
	 */
	private function resolve_supplier_name( string $supplier_key ): string
	{
		$supplier_key = strtolower( $supplier_key );

		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		foreach ( $clients as $client ) {
			if ( strtolower( $client['name'] ) === $supplier_key ) {
				return $client['name'];
			}
		}

		return '';
	}
}
