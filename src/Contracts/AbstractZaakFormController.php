<?php
/**
 * Abstract zaak form controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Contracts;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use GFFormsModel;
use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Traits\FormSetting;
use Throwable;

/**
 * Abstract zaak form controller.
 *
 * @since 1.0.0
 */
abstract class AbstractZaakFormController
{
	use FormSetting;

	protected array $entry;

	protected array $form;

	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
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
	public function add_zaak_reference_to_post( Zaak $zaak ): void
	{
		// Get the transaction post id created earlier in TransactionController.
		$transaction_post_id = gform_get_meta( $this->entry['id'], 'transaction_post_id' );

		$value = $zaak->getValue( 'identificatie' );
		if ( isset( $value ) ) {
			update_post_meta( $transaction_post_id, 'transaction_zaak_id', $value );
		}
	}

	/**
	 * Mark the transaction as successful.
	 */
	public function mark_transaction_success( Zaak $zaak ): void
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
					'transaction_actions' => 'Add icon here', // TODO: add icon handling.
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
			__( 'Er waren problemen bij het succesvol indienen van deze zaak, bekijk de transacties voor meer informatie.', 'owc-gravityforms-zgw' ),
			$message
		);

		// Log error if logger is available.
		if ( isset( $this->logger ) ) {
			$this->logger->error(
				sprintf( 'Transactie error: %s', $message )
			);
		}
	}

	/**
	 * Extracts a readable error message from an API exception.
	 */
	protected function extractApiErrorMessage(Throwable $e ): string
	{
		if ( ! method_exists( $e, 'getResponse' )) {
			return $e->getMessage() ?: 'Unknown error occurred.';
		}

		$response = $e->getResponse();

		if ( ! $response || ! method_exists( $response, 'getBody' )) {
			return $e->getMessage() ?: 'Unknown API response.';
		}

		$body = (string) $response->getBody();
		if ( ! $body) {
			return 'Empty API response body.';
		}

		$data = json_decode( $body, true );
		if (json_last_error() !== JSON_ERROR_NONE) {
			return 'Invalid JSON in API error response; Body: ' . $body;
		}

		// Priority: invalidParams → detail → title → generic fallback
		if ( ! empty( $data['invalidParams'] )) {
			$messages = array_map(
				fn($param ) => sprintf(
					"%s: %s",
					$param['name'] ?? '(unknown field)',
					$param['reason'] ?? '(no reason)'
				),
				$data['invalidParams']
			);

			return implode( '; ', $messages );
		}

		if ( ! empty( $data['detail'] )) {
			return $data['detail'];
		}

		if ( ! empty( $data['title'] )) {
			return $data['title'];
		}

		return 'An unknown API error occurred.';
	}
}
