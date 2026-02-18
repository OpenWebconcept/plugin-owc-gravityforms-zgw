<?php

declare(strict_types=1);

/**
 * Transaction Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Transactions\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use GFAPI;
use WP_Error;

/**
 * Transaction Controller.
 *
 * @since 1.1.0
 */
class RetryController
{
	public function handle(): mixed
	{
		if ( current_user_can( 'edit_owc_zgw_transactions' ) === false ) {
			return wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		check_ajax_referer( 'retry_submission' );

		$entry_id      = absint( $_POST['entry_id'] ?? 0 );
		$mapped_values = $this->get_mapped_fields_and_values( $entry_id ); // Is not necessarily needed, but validates the request and could be of use later.

		if ( is_wp_error( $mapped_values ) ) {
			return wp_send_json_error( array( 'message' => $mapped_values->get_error_message() ) );
		}

		$entry               = GFAPI::get_entry( $entry_id );
		$transaction_post_id = gform_get_meta( $entry_id, 'transaction_post_id' );
		$transaction_form_id = get_post_meta( $transaction_post_id, 'transaction_form_id', true );
		$form                = GFAPI::get_form( $transaction_form_id );

		if ( ! $entry || ! $form ) {
			return wp_send_json_error( array( 'message' => 'Invalid entry or form.' ) );
		}

		// Is used for deletion when the retry is successful.
		$zaak_uuid      = (string) ( get_post_meta( $transaction_post_id, 'transaction_zaak_uuid', true ) ?: '' );
		$zaak_reference = (string) ( get_post_meta( $transaction_post_id, 'transaction_zaak_id', true ) ?: '' );

		try {
			( new ExecuteRetryController( $transaction_post_id, $zaak_uuid, $zaak_reference, $entry, $form ) )->retry();
		} catch ( Exception $e ) {
			return wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		return wp_send_json_success( array( 'message' => 'Retry succeeded!' ) );
	}

	/**
	 * Validates the request and returns mapped field values or WP_Error.
	 */
	private function get_mapped_fields_and_values(int $entry_id ): array|WP_Error
	{
		if ( ! $entry_id ) {
			return new WP_Error( 'missing_entry_id', 'Entry ID is missing.' );
		}

		$transaction_post_id = gform_get_meta( $entry_id, 'transaction_post_id' );

		if ( ! $transaction_post_id ) {
			return new WP_Error( 'missing_transaction', 'No linked transaction found for this entry.' );
		}

		$transaction_form_id = get_post_meta( $transaction_post_id, 'transaction_form_id', true );

		if ( ! $transaction_form_id ) {
			return new WP_Error( 'missing_form', 'No linked form found for this entry.' );
		}

		$mapped_zgw_fields = $this->get_mapped_zgw_form_fields( (int) $transaction_form_id );

		if ( array() === $mapped_zgw_fields ) {
			return new WP_Error( 'missing_zgw_fields', 'No valid linked ZGW fields found in the form.' );
		}

		$meta = $this->get_entry_meta( $entry_id );

		if ( array() === $meta ) {
			return new WP_Error( 'missing_entry_metadata', 'No entry metadata found.' );
		}

		$mapped_indexes_to_field_ids = wp_list_pluck( $mapped_zgw_fields, 'id', 'mappedFieldValueZGW' );
		$mapped_values               = $this->map_indexes_to_values( $mapped_indexes_to_field_ids, $meta );

		if ( array() === $mapped_values ) {
			return new WP_Error( 'missing_values', 'No valid values found for the linked ZGW fields.' );
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
