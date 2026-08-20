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
use OWCGravityFormsZGW\Transactions\TransactionPostType;
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
			return wp_send_json_error( array( 'message' => __( 'Geen toegang.', 'owc-gravityforms-zgw' ) ) );
		}

		check_ajax_referer( 'retry_submission' );

		$entry_id      = absint( $_POST['entry_id'] ?? 0 );
		$mapped_values = $this->get_mapped_fields_and_values( $entry_id );

		if ( is_wp_error( $mapped_values ) ) {
			return wp_send_json_error( array( 'message' => $mapped_values->get_error_message() ) );
		}

		$entry               = GFAPI::get_entry( $entry_id );
		$transaction_post_id = gform_get_meta( $entry_id, 'transaction_post_id' );
		$transaction_form_id = get_post_meta( $transaction_post_id, 'transaction_form_id', true );
		$form                = GFAPI::get_form( $transaction_form_id );

		if ( ! $entry || ! $form ) {
			return wp_send_json_error( array( 'message' => __( 'Ongeldige inzending of formulier.', 'owc-gravityforms-zgw' ) ) );
		}

		$original_zaak_uuid      = (string) ( get_post_meta( $transaction_post_id, 'transaction_zaak_uuid', true ) ?: '' );
		$original_zaak_reference = (string) ( get_post_meta( $transaction_post_id, 'transaction_zaak_id', true ) ?: '' );

		try {
			( new ExecuteRetryController( $transaction_post_id, $original_zaak_uuid, $original_zaak_reference, $entry, $form ) )->retry();
		} catch ( Exception $e ) {
			return wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'columns' => $this->get_updated_columns( (int) $transaction_post_id ),
				)
			);
		}

		$note_ids = (array) get_post_meta( $transaction_post_id, 'transaction_note_ids', true );

		foreach ( $note_ids as $note_id ) {
			GFAPI::delete_note( (int) $note_id );
		}

		if ( array() !== $note_ids ) {
			delete_post_meta( (int) $transaction_post_id, 'transaction_note_ids' );
		}

		return wp_send_json_success(
			array(
				'message' => __( 'Opnieuw uitvoeren gelukt.', 'owc-gravityforms-zgw' ),
				'columns' => $this->get_updated_columns( (int) $transaction_post_id ),
			)
		);
	}

	/**
	 * Returns rendered HTML for the columns that can change after a retry.
	 */
	private function get_updated_columns( int $transaction_post_id ): array
	{
		$columns = array( 'transaction_status', 'transaction_zaak_id', 'transaction_message' );
		$result  = array();

		foreach ( $columns as $column ) {
			$result[ $column ] = TransactionPostType::render_column( $column, $transaction_post_id );
		}

		return $result;
	}

	/**
	 * Validates the request and returns mapped field values or WP_Error.
	 */
	private function get_mapped_fields_and_values( int $entry_id ): array|WP_Error
	{
		if ( ! $entry_id ) {
			return new WP_Error( 'missing_entry_id', __( 'Inzending ID ontbreekt.', 'owc-gravityforms-zgw' ) );
		}

		$transaction_post_id = gform_get_meta( $entry_id, 'transaction_post_id' );

		if ( ! $transaction_post_id ) {
			return new WP_Error( 'missing_transaction', __( 'Geen gekoppelde transactie gevonden voor deze inzending.', 'owc-gravityforms-zgw' ) );
		}

		$transaction_form_id = get_post_meta( $transaction_post_id, 'transaction_form_id', true );

		if ( ! $transaction_form_id ) {
			return new WP_Error( 'missing_form', __( 'Geen gekoppeld formulier gevonden voor deze inzending.', 'owc-gravityforms-zgw' ) );
		}

		$mapped_zgw_fields = $this->get_mapped_zgw_form_fields( (int) $transaction_form_id );

		if ( array() === $mapped_zgw_fields ) {
			return new WP_Error( 'missing_zgw_fields', __( 'Geen geldige ZGW-velden gevonden in het formulier.', 'owc-gravityforms-zgw' ) );
		}

		$meta = $this->get_entry_meta( $entry_id );

		if ( array() === $meta ) {
			return new WP_Error( 'missing_entry_metadata', __( 'Geen inzendingsmetadata gevonden.', 'owc-gravityforms-zgw' ) );
		}

		$mapped_indexes_to_field_ids = wp_list_pluck( $mapped_zgw_fields, 'id', 'mappedFieldValueZGW' );
		$mapped_values               = $this->map_indexes_to_values( $mapped_indexes_to_field_ids, $meta );

		if ( array() === $mapped_values ) {
			return new WP_Error( 'missing_values', __( 'Geen geldige waarden gevonden voor de gekoppelde ZGW-velden.', 'owc-gravityforms-zgw' ) );
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
			function ( $field ) {
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

		return $entry;
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
