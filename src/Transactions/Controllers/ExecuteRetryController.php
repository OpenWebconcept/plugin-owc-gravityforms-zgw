<?php

declare(strict_types=1);

/**
 * Execute Retry Controller.
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
use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Actions\DeleteZaakAction;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Execute Retry Controller.
 *
 * @since 1.1.0
 */
class ExecuteRetryController
{
	protected string $transaction_post_id;
	protected string $original_zaak_uuid;
	protected string $original_zaak_reference;
	protected array $entry;
	protected array $form;
	protected array $supplier_config;

	public function __construct( string $transaction_post_id, string $original_zaak_uuid, string $original_zaak_reference, array $entry = array(), array $form = array() )
	{
		$this->transaction_post_id     = $transaction_post_id;
		$this->original_zaak_uuid      = $original_zaak_uuid;
		$this->original_zaak_reference = $original_zaak_reference;
		$this->entry                   = $entry;
		$this->form                    = $form;
		$this->supplier_config         = FormUtils::get_supplier_config( $form );
	}

	/**
	 * @throws Exception When the retry attempt fails or cleanup after retry fails.
	 */
	public function retry(): void
	{
		$this->attempt_pending_deletions();

		$retried_zaak = ( new ZaakController() )->handle( $this->entry, $this->form );

		if ( ! $retried_zaak instanceof Zaak ) {
			throw new Exception( __( 'Herpoging mislukt.', 'owc-gravityforms-zgw' ) );
		}

		if ( $retried_zaak->getValue( 'creation_failed', false ) === true ) {
			$this->handle_failure_retried_zaak( $retried_zaak );
		}

		if ( '' !== $this->original_zaak_uuid ) {
			$this->delete_original_zaak();
		}
	}

	/**
	 * @throws Exception When the retry attempt failed and cleanup of the newly created zaak fails.
	 */
	private function handle_failure_retried_zaak( Zaak $retried_zaak ): void
	{
		$this->delete_retried_zaak( $retried_zaak->getValue( 'uuid', '' ) );
		$this->overwrite_transaction_meta_with_original_zaak();

		// translators: %s is the zaak reference number.
		throw new Exception( sprintf( __( 'Herpoging mislukt voor zaak %s.', 'owc-gravityforms-zgw' ), $this->original_zaak_reference ) );
	}

	/**
	 * Deletes the newly created zaak when the retry attempt itself failed.
	 * Queues the UUID for later cleanup if deletion fails.
	 *
	 * @throws Exception When the newly created zaak could not be deleted after a failed retry.
	 */
	private function delete_retried_zaak( string $zaak_uuid ): void
	{
		try {
			( new DeleteZaakAction( $this->supplier_config ) )->delete( $zaak_uuid );
		} catch ( Exception $e ) {
			$this->queue_failed_deletion( $zaak_uuid );
			// translators: %1$s is the zaak UUID, %2$s is the error message.
			$this->supplement_transaction_message( sprintf( __( 'Kon nieuw aangemaakte zaak %1$s niet verwijderen na mislukte herpoging: %2$s', 'owc-gravityforms-zgw' ), $zaak_uuid, $e->getMessage() ) );
			$this->overwrite_transaction_meta_with_original_zaak();
			// translators: %s is the zaak UUID.
			throw new Exception( sprintf( __( 'Kon nieuw aangemaakte zaak %s niet verwijderen na mislukte herpoging.', 'owc-gravityforms-zgw' ), $zaak_uuid ) );
		}
	}

	/**
	 * Deletes the original failed zaak after a successful retry.
	 * Queues the UUID for later cleanup if deletion fails, without affecting the retry result.
	 */
	private function delete_original_zaak(): void
	{
		try {
			( new DeleteZaakAction( $this->supplier_config ) )->delete( $this->original_zaak_uuid );
		} catch ( Exception $e ) {
			$this->queue_failed_deletion( $this->original_zaak_uuid );
			// translators: %1$s is the zaak reference number, %2$s is the error message.
			$this->supplement_transaction_message( sprintf( __( 'Kon originele zaak %1$s niet verwijderen na geslaagde herpoging: %2$s', 'owc-gravityforms-zgw' ), $this->original_zaak_reference, $e->getMessage() ) );
		}
	}

	/**
	 * Attempts to delete any zaaks that previously failed to be deleted.
	 * Successful deletions are removed from the queue. Runs best-effort — failures are kept in the queue for the next attempt.
	 */
	private function attempt_pending_deletions(): void
	{
		$pending = $this->get_pending_deletions();

		if ( empty( $pending ) ) {
			return;
		}

		$remaining = array();

		foreach ( $pending as $zaak_uuid ) {
			try {
				( new DeleteZaakAction( $this->supplier_config ) )->delete( $zaak_uuid );
			} catch ( Exception ) {
				$remaining[] = $zaak_uuid;
			}
		}

		$this->set_pending_deletions( $remaining );
	}

	/**
	 * Adds a zaak UUID to the list of pending deletions, if not already queued.
	 */
	private function queue_failed_deletion( string $zaak_uuid ): void
	{
		$pending = $this->get_pending_deletions();

		if ( in_array( $zaak_uuid, $pending, true ) ) {
			return;
		}

		$pending[] = $zaak_uuid;

		$this->set_pending_deletions( $pending );
	}

	private function get_pending_deletions(): array
	{
		return (array) get_post_meta( $this->transaction_post_id, 'transaction_pending_deletions', true );
	}

	private function set_pending_deletions( array $uuids ): void
	{
		update_post_meta( $this->transaction_post_id, 'transaction_pending_deletions', $uuids );
	}

	/**
	 * Overwrites the transaction meta with the original zaak values after a failed retry.
	 *
	 * ZaakController writes the new zaak UUID to the meta immediately on creation (before uploads or
	 * any other step can fail). If the retry fails, the meta must be restored to the original zaak so
	 * the next retry knows which zaak to delete after it succeeds. The failed new zaak is handled
	 * separately via the pending deletions queue.
	 */
	private function overwrite_transaction_meta_with_original_zaak(): void
	{
		update_post_meta( $this->transaction_post_id, 'transaction_zaak_uuid', '' !== $this->original_zaak_uuid ? $this->original_zaak_uuid : null );
		update_post_meta( $this->transaction_post_id, 'transaction_zaak_id', '' !== $this->original_zaak_reference ? $this->original_zaak_reference : null );
	}

	private function supplement_transaction_message( string $message ): void
	{
		$existing_message = get_post_meta( $this->transaction_post_id, 'transaction_message', true );
		$updated_message  = $existing_message . "\n" . $message;

		update_post_meta( $this->transaction_post_id, 'transaction_message', $updated_message );
	}
}
