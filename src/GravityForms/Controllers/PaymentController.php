<?php

declare(strict_types=1);

/**
 * Payment controller.
 *
 * This controller is responsible for handling payment status changes and acts accordingly, such as deleting created Zaken after a failed payment.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.9.0
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RuntimeException;
use OWCGravityFormsZGW\GravityForms\Traits\EntryNote;
use Exception;
use GFAPI;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Actions\DeleteZaakAction;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\Controllers\ActionSchedulerController;
use OWCGravityFormsZGW\Transactions\Controllers\TransactionController;

/**
 * Payment controller.
 *
 * @since 1.9.0
 */
class PaymentController
{
	use EntryNote;

	public const SUCCESSFUL_PAYMENT_STATUS = 'Paid';
	public const PENDING_PAYMENT_STATUS    = 'Pending';
	public const FAILED_PAYMENT_STATUSES   = array(
		'Failed',
		'Refunded',
		'Voided',
		'Cancelled',
	);

	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	public function post_payment_pending_expired(array $entry, array $action ): void
	{
		if ( self::PENDING_PAYMENT_STATUS !== $action['payment_status'] ) {
			return;
		}

		$this->handle_zaak_deletion( $entry );
	}

	/**
	 * Is called after a payment status is added or changed.
	 */
	public function post_payment_failed(array $entry, array $action ): void
	{
		if ( ! in_array( $action['payment_status'], self::FAILED_PAYMENT_STATUSES ) ) {
			return;
		}

		// Guard against out-of-order notifications: a delayed failure/cancellation
		// from a previous attempt can arrive after a successful payment has already
		// been confirmed. If the entry is already paid, never treat it as failed.
		if ( self::SUCCESSFUL_PAYMENT_STATUS === ( $entry['payment_status'] ?? '' ) ) {
			return;
		}

		$this->handle_zaak_deletion( $entry );
	}

	protected function handle_zaak_deletion(array $entry )
	{
		$zaak_uuid = $this->get_created_zaak_uuid( $entry );

		if ( '' === $zaak_uuid ) {
			return;
		}

		$form = GFAPI::get_form( $entry['form_id'] );

		try {
			$this->delete_zaak( $zaak_uuid, $form );
		} catch ( Exception $e ) {
			$this->logger->error(
				'Error deleting zaak after failed payment',
				array(
					'entry_id' => $entry['id'],
					'form_id'  => $entry['form_id'],
					'error'    => $e->getMessage(),
				)
			);

			$this->add_entry_note( $entry['id'], __( 'Fout bij verwijderen van aangemaakte zaak na mislukte betaling. Handmatig verwijderen vereist.', 'owc-gravityforms-zgw' ) );
			$this->update_entry_payment_status( $entry, 'Cancelled' );

			return;
		}

		$this->add_entry_note( $entry['id'], __( 'Aangemaakte zaak geannuleerd, betaling mislukt.', 'owc-gravityforms-zgw' ), 'info' );
		$this->update_entry_payment_status( $entry, 'Cancelled' );
	}

	protected function get_created_zaak_uuid(array $entry ): string
	{
		$created_zaak_uuid = gform_get_meta( $entry['id'], 'owc_gz_created_zaak_uuid' );

		return is_string( $created_zaak_uuid ) && '' !== trim( $created_zaak_uuid ) ? trim( $created_zaak_uuid ) : '';
	}

	protected function delete_zaak(string $zaak_uuid, array $form ): void
	{
		$supplier_config = FormUtils::get_supplier_config( $form );

		if ( 0 === count( $supplier_config ) ) {
			$this->logger->error( sprintf( 'No supplier configuration found for form when trying to delete Zaak: %s', $zaak_uuid ), array( 'form_id' => $form['id'] ) );
			throw new RuntimeException( sprintf( 'No supplier configuration found for form when trying to delete Zaak: %s', $zaak_uuid ) );
		}

		( new DeleteZaakAction( $supplier_config ) )->delete( $zaak_uuid );
	}

	public function post_payment_completed(array $entry, array $action ): void
	{
		if ( self::SUCCESSFUL_PAYMENT_STATUS !== $action['payment_status'] ) {
			return;
		}

		$form = GFAPI::get_form( $entry['form_id'] );

		( new ActionSchedulerController() )->schedule_single_actions( $entry, $form );
		( new TransactionController() )->create( $entry, $form );

		$this->add_entry_note( $entry['id'], __( 'Zaak definitief, betaling succesvol.', 'owc-gravityforms-zgw' ), 'success' );
	}

	private function update_entry_payment_status(array $entry, string $status ): void
	{
		$fresh_entry = GFAPI::get_entry( $entry['id'] );

		GFAPI::update_entry(
			array_merge(
				is_wp_error( $fresh_entry ) ? $entry : $fresh_entry,
				array(
					'payment_status' => $status,
				)
			)
		);
	}
}
