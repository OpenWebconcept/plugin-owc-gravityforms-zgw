<?php
/**
 * Execute Retry Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Transactions\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use OWCGravityFormsZGW\Actions\DeleteZaakAction;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWC\ZGW\Entities\Zaak;

/**
 * Execute Retry Controller.
 *
 * @since NEXT
 */
class ExecuteRetryController
{
	protected string $transaction_post_id;
	protected string $zaak_uuid;
	protected string $zaak_reference;
	protected array $entry;
	protected array $form;
	protected array $supplier_config;

	public function __construct(string $transaction_post_id, string $zaak_uuid, string $zaak_reference, array $entry = array(), array $form = array() )
	{
		$this->transaction_post_id = $transaction_post_id;
		$this->zaak_uuid           = $zaak_uuid;
		$this->zaak_reference      = $zaak_reference;
		$this->entry               = $entry;
		$this->form                = $form;
		$this->supplier_config     = FormUtils::get_supplier_config( $form );
	}

	/**
	 * @throws Exception
	 */
	public function retry(): void
	{
		$retried_zaak = ( new ZaakController() )->handle( $this->entry, $this->form );

		if ( ! $retried_zaak instanceof Zaak ) {
			$this->restore_original_transaction_meta();

			throw new Exception( 'retry failed' );
		}

		if ( $retried_zaak->getValue( 'creation_failed', false ) === true ) {
			$this->handle_failure_retried_zaak( $retried_zaak );
		}

		$this->delete_zaak( $this->zaak_uuid, $this->zaak_reference );
	}

	/**
	 * @throws Exception
	 */
	private function handle_failure_retried_zaak(Zaak $retried_zaak ): void
	{
		$this->delete_zaak( $retried_zaak->getValue( 'uuid', '' ), $retried_zaak->getValue( 'identificatie', '' ) );
		$this->restore_original_transaction_meta();

		throw new Exception( sprintf( 'Retry failed for zaak reference: %s', $this->zaak_reference ) );
	}

	/**
	 * @throws Exception
	 */
	private function delete_zaak( string $zaak_uuid, string $zaak_reference ): void
	{
		$is_handling_retried_zaak = $this->zaak_uuid !== $zaak_uuid;

		try {
			( new DeleteZaakAction( $this->supplier_config ) )->delete( $zaak_uuid );
		} catch ( Exception $e ) {
			if ( $is_handling_retried_zaak ) {
				$this->supplement_transaction_message( sprintf( 'Failed to delete retried zaak %s after retry failure: %s', $zaak_reference, $e->getMessage() ) );
				throw new Exception( sprintf( 'failed to delete retried zaak %s after retry failure.', $zaak_reference ) );
			}

			$this->supplement_transaction_message( sprintf( 'Failed to delete previous failed zaak %s after successful retry: %s', $zaak_reference, $e->getMessage() ) );
			throw new Exception( 'retry succeeded, but failed to delete previous failed zaak.' );
		}
	}

	/**
	 * Restore original transaction meta after retry failure.
	 */
	private function restore_original_transaction_meta(): void
	{
		update_post_meta( $this->transaction_post_id, 'transaction_zaak_uuid', $this->zaak_uuid ?: null );
		update_post_meta( $this->transaction_post_id, 'transaction_zaak_id', $this->zaak_reference ?: null );
	}

	private function supplement_transaction_message( string $message ): void
	{
		$existing_message = get_post_meta( $this->transaction_post_id, 'transaction_message', true );
		$updated_message  = $existing_message . "\n" . $message;

		update_post_meta( $this->transaction_post_id, 'transaction_message', $updated_message );
	}
}
