<?php
/**
 * Transaction Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Transactions\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use GFAPI;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Services\EncryptionService;
use WP_Error;

/**
 * Transaction Controller.
 *
 * @since NEXT
 */
class RetryController
{
	public function retry(): mixed
	{
		check_ajax_referer( 'retry_submission' );

		$mapped_values = $this->validate_request();

		if ( is_wp_error( $mapped_values ) ) {
			wp_send_json_error( array( 'message' => $mapped_values->get_error_message() ) );
		}

		// Retry, finish later.

		return wp_send_json_success( array( 'message' => 'Opnieuw uitvoeren gelukt!' ) );
	}

	/**
	 * Validates the request and returns mapped field values or WP_Error.
	 */
	private function validate_request(): array|WP_Error
	{
		$entry_id = absint( $_POST['entry_id'] ?? 0 );

		if ( ! $entry_id ) {
			return new WP_Error( 'missing_entry_id', 'Entry ID ontbreekt.' );
		}

		$transaction_post_id = gform_get_meta( $entry_id, 'transaction_post_id' );

		if ( ! $transaction_post_id ) {
			return new WP_Error( 'missing_transaction', 'Geen gekoppelde transactie gevonden voor deze inzending.' );
		}

		$transaction_form_id = get_post_meta( $transaction_post_id, 'transaction_form_id', true );

		if ( ! $transaction_form_id ) {
			return new WP_Error( 'missing_form', 'Geen gekoppeld formulier gevonden voor deze inzending.' );
		}

		$mapped_zgw_fields = $this->get_mapped_zgw_form_fields( $transaction_form_id );

		if (array() === $mapped_zgw_fields ) {
			return new WP_Error( 'missing_zgw_fields', 'Geen geldige gekoppelde ZGW velden gevonden in het formulier.' );
		}

		$meta = $this->get_entry_meta( $entry_id );

		if (array() === $meta ) {
			return new WP_Error( 'missing_entry_data', 'Ingevoerde gegevens niet gevonden.' );
		}

		$mapped_indexes_to_field_ids = wp_list_pluck( $mapped_zgw_fields, 'id', 'mappedFieldValueZGW' );
		$mapped_values               = $this->map_indexes_to_values( $mapped_indexes_to_field_ids, $meta );

		if ( array() === $mapped_values ) {
			return new WP_Error( 'missing_values', 'Geen geldige waarden gevonden voor de gekoppelde ZGW velden.' );
		}

		return $mapped_values;
	}

	/**
	 * Returns form fields that have a valid mappedFieldValueZGW property, used for the building of the retry payload.
	 */
	private function get_mapped_zgw_form_fields( int $transaction_form_id ): array
	{
		$form = GFAPI::get_form( $transaction_form_id );

		if ( ! $form ) {
			return array();
		}

		return array_filter(
			$form['fields'],
			function ($field ) {
				return isset( $field->mappedFieldValueZGW ) && is_string( $field->mappedFieldValueZGW ) && 0 < strlen( $field->mappedFieldValueZGW );
			}
		);
	}

	private function get_entry_meta( int $entry_id ): array
	{
		$entry = GFAPI::get_entry( $entry_id );

		if ( ! $entry ) {
			return array();
		}

		return $entry ?: array();
	}

	private function map_indexes_to_values( array $mapped_indexes_to_field_ids, array $meta ): array
	{
		$mapped_values = array();

		foreach ( $mapped_indexes_to_field_ids as $index => $field_id ) {
			if ( isset( $meta[ $field_id ] ) ) {
				$mapped_values[ $index ] = $meta[ $field_id ];
			}
		}

		return $mapped_values;
	}
}
