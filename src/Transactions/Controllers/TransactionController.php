<?php

declare(strict_types=1);

/**
 * Transaction Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Transactions\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use OWCGravityFormsZGW\Auth\DigiD;
use OWCGravityFormsZGW\Auth\eHerkenning;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\Services\EncryptionService;
use OWCGravityFormsZGW\Transactions\TransactionStatus;

/**
 * Transaction Controller.
 *
 * @since 1.0.0
 */
class TransactionController
{
	const POST_TYPE = 'owc_zgw_transaction';

	private array $statuses;

	public function __construct()
	{
		$this->statuses = array(
			TransactionStatus::SUCCESS => 'transaction_success',
			TransactionStatus::PENDING => 'transaction_pending',
			TransactionStatus::FAILED  => 'transaction_failed',
		);
	}

	/**
	 * Create a transaction.
	 */
	public function create( array $entry, array $form ): void
	{
		// Only create transaction for ZGW enabled forms.
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return;
		}

		if ( FormUtils::entry_has_pending_payment( $entry ) ) {
			return;
		}

		// Don't create a duplicate transaction when one was already created at
		// submission time (e.g. when gform_post_payment_completed fires after payment).
		if ( gform_get_meta( $entry['id'], 'transaction_post_id' ) ) {
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => sprintf( 'Entry %d (Form %d)', $entry['id'], $entry['form_id'] ),
				'post_status' => $this->statuses[ TransactionStatus::PENDING ],
				'meta_input'  => $this->add_metadata( $entry, $form ),
			)
		);

		if ( $post_id > 0 ) {
			gform_update_meta( $entry['id'], 'transaction_post_id', $post_id ); // Save reference to entry so other processes can find the post.
			gform_update_meta( $entry['id'], 'owc_zgw_pending_bsn', '' ); // Clear any previously stored pending BSN since transaction is now created.
			gform_update_meta( $entry['id'], 'owc_zgw_pending_kvk', '' ); // Clear any previously stored pending KVK since transaction is now created.
		}
	}

	/**
	 * Capture encrypted BSN/KVK into entry meta at submission time for entries with a pending payment.
	 *
	 * When payment is required upfront, transaction creation is intentionally deferred until
	 * payment completes. The payment completion webhook runs outside the user's PHP session,
	 * so DigiD/eHerkenning session data is unavailable at that point. Capturing the identification
	 * here, while the session is still active, ensures add_metadata() can fall back to it.
	 *
	 * @since NEXT
	 */
	public function capture_identification_for_pending_payment( array $entry, array $form ): void
	{
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return;
		}

		if ( ! FormUtils::entry_has_pending_payment( $entry ) ) {
			return;
		}

		try {
			$bsn = FormUtils::overwrite_bsn_form_setting( $form ) ?: DigiD::make()->bsn();
			$kvk = FormUtils::overwrite_kvk_form_setting( $form ) ?: eHerkenning::make()->kvk();

			if ( $bsn ) {
				gform_update_meta( $entry['id'], 'owc_zgw_pending_bsn', EncryptionService::encrypt( $bsn ) );
			}

			if ( $kvk ) {
				gform_update_meta( $entry['id'], 'owc_zgw_pending_kvk', EncryptionService::encrypt( $kvk ) );
			}
		} catch ( Exception $e ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( sprintf( 'Error capturing identification for pending payment entry "%s": %s', $entry['id'], $e->getMessage() ) );
		}
	}

	private static function add_metadata( array $entry, array $form ): array
	{
		$standard = array(
			'transaction_form_id'  => $entry['form_id'],
			'transaction_entry_id' => $entry['id'],
			'transaction_datetime' => $entry['date_created'],
		);

		try {
			$bsn = FormUtils::overwrite_bsn_form_setting( $form ) ?: DigiD::make()->bsn();
			$kvk = FormUtils::overwrite_kvk_form_setting( $form ) ?: eHerkenning::make()->kvk();

			$sensitive = array(
				'transaction_user_bsn' => $bsn ? EncryptionService::encrypt( $bsn ) : self::get_pending_encrypted_identification( $entry['id'], 'owc_zgw_pending_bsn' ),
				'transaction_user_kvk' => $kvk ? EncryptionService::encrypt( $kvk ) : self::get_pending_encrypted_identification( $entry['id'], 'owc_zgw_pending_kvk' ),
			);
		} catch ( Exception $e ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Error encrypting sensitive transaction metadata: ' . $e->getMessage() );
			$sensitive = array();
		}

		return array_merge( array_filter( $standard ), array_filter( $sensitive ) );
	}

	/**
	 * @since NEXT
	 */
	private static function get_pending_encrypted_identification( int|string $entry_id, string $meta_key ): ?string
	{
		$value = gform_get_meta( $entry_id, $meta_key );

		return is_string( $value ) && '' !== $value ? $value : null;
	}
}
