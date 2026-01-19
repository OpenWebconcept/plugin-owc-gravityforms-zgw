<?php
/**
 * Verzoek controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFFormsModel;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\LoggerZGW;
use Throwable;

/**
 * Verzoek controller.
 *
 * @since NEXT
 */
class VerzoekController
{
	protected array $entry;
	protected array $form;
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	public function handle( array $entry, array $form ): ?array
	{
		$this->set_form_data( $entry, $form );

		if ( ! FormUtils::is_verzoek_form( $this->form ) ) {
			return null;
		}

		try {
			$verzoek = $this->create_verzoek();
			$this->handle_uploads( $verzoek );

			$this->mark_transaction_success();
		} catch ( Throwable $e ) {
			$this->mark_transaction_failed( $e->getMessage() );

			return null;
		}

		return $verzoek;
	}

	protected function create_verzoek(): array
	{
		$action  = new CreateVerzoekAction( $this->entry, $this->form );
		$verzoek = $action->create();

		$this->add_verzoek_reference_to_post( $verzoek );

		return $verzoek;
	}

	protected function handle_uploads( array $verzoek ): void
	{
		$uploads_controller = new VerzoekUploadsController();

		$uploads_controller->set_form_data( $this->entry, $this->form );
		$uploads_controller->handle( $verzoek );
	}

	/**
	 * Set the form and entry data for the controller.
	 */
	public function set_form_data(array $entry, array $form ): void
	{
		$this->entry = $entry;
		$this->form  = $form;
	}

	/**
	 * Add the Zaak id/reference to the transaction post.
	 */
	public function add_verzoek_reference_to_post( array $verzoek ): void
	{
		// Get the transaction post id created earlier in TransactionController.
		$transaction_post_id = gform_get_meta( $this->entry['id'], 'transaction_post_id' );
		$value               = $verzoek['id'] ?? null;

		if ( isset( $value ) ) {
			update_post_meta( $transaction_post_id, 'transaction_verzoek_id', $value );
		}

		$value = $verzoek['uuid'] ?? null;
		if ( isset( $value ) ) {
			update_post_meta( $transaction_post_id, 'transaction_verzoek_uuid', $value );
		}
	}

	/**
	 * Mark the transaction as successful.
	 */
	public function mark_transaction_success(): void
	{
		// Get the transaction post id created earlier in TransactionController.
		$transaction_post_id = gform_get_meta( $this->entry['id'], 'transaction_post_id' );

		if ( ! $transaction_post_id ) {
			$this->logger->error( 'No transaction post linked to this entry.' );
			return;
		}

		// Mark transaction as success.
		wp_update_post(
			array(
				'ID'          => (int) $transaction_post_id,
				'post_status' => 'transaction_success',
				'meta_input'  => array(
					'transaction_actions' => '',
					'transaction_message' => '',
				),
			)
		);
	}

	/**
	 * Mark the transaction as failed.
	 * Add a message to the Transaction post and add a note to the GravityForms entry.
	 */
	public function mark_transaction_failed( string $message ): void
	{
		// Get the transaction post id created earlier in TransactionController.
		$transaction_post_id = gform_get_meta( $this->entry['id'], 'transaction_post_id' );

		if ( ! $transaction_post_id ) {
			$this->logger->error( 'No transaction post linked to this entry.' );
			return;
		}

		// Update the transaction post status to failed.
		wp_update_post(
			array(
				'ID'          => $transaction_post_id,
				'post_status' => 'transaction_failed',
				'meta_input'  => array(
					'transaction_actions' => untrailingslashit( OWC_GRAVITYFORMS_ZGW_PLUGIN_URL ) . '/assets/images/icon-retry.svg',
				),
			)
		);

		// Update transaction message.
		update_post_meta( $transaction_post_id, 'transaction_message', $message );

		// Add a note to the entry with the failure message.
		GFFormsModel::add_note(
			$this->entry['id'],
			0,
			__( 'Systeem', 'owc-gravityforms-zgw' ),
			__( 'Er waren problemen bij het succesvol indienen van dit verzoek, bekijk de transacties voor meer informatie.', 'owc-gravityforms-zgw' ),
			$message
		);

		// Log error if logger is available.
		if ( isset( $this->logger ) ) {
			$this->logger->error(
				sprintf( 'Transactie error: %s', $message )
			);
		}
	}
}
