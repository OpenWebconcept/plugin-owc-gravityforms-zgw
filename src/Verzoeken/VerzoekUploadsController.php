<?php
/**
 * Zaak uploads controller.
 *
 * This controller is responsible for handling form submissions before (pre) form submission
 * and delegates multiple actions towards the ZGW API.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\LoggerZGW;
use Throwable;

/**
 * Zaak uploads controller.
 *
 * @since 1.0.0
 */
class VerzoekUploadsController
{
	protected array $entry;
	protected array $form;
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	/**
	 * Initialize Zaak uploads and handle accordingly.
	 */
	public function handle( array $verzoek ): void
	{
		try {
			$this->handle_verzoek_uploads( $verzoek );
		} catch ( Throwable $e ) {
			$message = sprintf(
				'Error processing uploads: %s',
				$e->getMessage()
			);

			$this->logger->error( $message );

			throw new Exception( $message, 400, $e );
		}
	}

	/**
	 * Add uploads to Zaak using the supplier-specific Action class.
	 */
	public function handle_verzoek_uploads( array $verzoek ): void
	{
		try {
			$action = new CreateUploadedDocumentsVerzoekAction(
				$this->entry,
				$this->form,
				$verzoek
			);

			$result = $action->add_uploaded_documents();

			if ( false === $result ) {
				throw new Exception(
					sprintf(
						'Not all uploads were successfully added to verzoek %s.',
						$verzoek['uuid']
					),
					400
				);
			}

			// $result === null → no uploads mapped, skip silently
			// $result === true → all uploads succeeded
		} catch ( Throwable $e ) {
			$reason_message = $this->extract_api_error_message( $e );

			throw new Exception(
				esc_html( $reason_message ),
				400,
				$e
			);
		}
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
		 * Extracts a readable error message from an API exception.
		 */
	protected function extract_api_error_message(Throwable $e ): string
	{
		if ( ! method_exists( $e, 'getResponse' ) ) {
			return $e->getMessage() ?: 'Unknown error occurred.';
		}

		$response = $e->getResponse();

		if ( ! $response || ! method_exists( $response, 'getBody' ) ) {
			return $e->getMessage() ?: 'Unknown API response.';
		}

		$body = (string) $response->getBody();
		if ( ! $body ) {
			return 'Empty API response body.';
		}

		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return 'Invalid JSON in API error response; Body: ' . $body;
		}

		// Priority: invalidParams → detail → title → generic fallback
		if ( ! empty( $data['invalidParams'] ) ) {
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

		if ( ! empty( $data['detail'] ) ) {
			return $data['detail'];
		}

		if ( ! empty( $data['title'] ) ) {
			return $data['title'];
		}

		return 'An unknown API error occurred.';
	}
}
