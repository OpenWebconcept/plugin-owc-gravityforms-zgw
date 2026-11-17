<?php

declare(strict_types=1);

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

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Actions\CreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use Throwable;

/**
 * Zaak uploads controller.
 *
 * @since 1.0.0
 */
class ZaakUploadsController extends AbstractZaakFormController
{
	/**
	 * Initialize Zaak uploads and handle accordingly.
	 *
	 * @throws ZaakUploadException
	 */
	public function handle( Zaak $zaak, array $supplier_config ): void
	{
		try {
			$this->handle_zaak_uploads( $zaak, $supplier_config );
		} catch ( Throwable $e ) {
			$message = sprintf(
				'Error processing uploads: %s',
				$e->getMessage()
			);

			$this->logger->error( $message );

			throw new ZaakUploadException( $message, 400, $e );
		}
	}

	/**
	 * Add uploads to Zaak using the supplier-specific Action class.
	 *
	 * @throws ZaakUploadException
	 */
	protected function handle_zaak_uploads( Zaak $zaak, array $supplier_config ): void
	{
		$action = null;

		try {
			$action = $this->create_uploaded_documents_action( $zaak, $supplier_config );

			$result = $action->add_uploaded_documents();

			if ( $result === false ) {
				throw new ZaakUploadException(
					sprintf(
						'An upload could not be added to zaak %s. %s',
						$zaak->getValue( 'identificatie', 'unknown' ),
						$action->get_upload_error()
					),
					400
				);
			}

			// $result === null → no uploads mapped, skip silently
			// $result === true → all uploads succeeded
		} catch ( Throwable $e ) {
			$reason_message = $this->extract_api_error_message( $e );
			$failed_file    = $action ? $action->get_upload_error_file() : '';

			// Exceptions thrown by this method already contain the file name.
			if ( '' !== $failed_file && ! $e instanceof ZaakUploadException ) {
				$reason_message = sprintf( '%s: %s', $failed_file, $reason_message );
			}

			throw new ZaakUploadException(
				$reason_message,
				400,
				$e
			);
		}
	}

	/**
	 * @since 1.19.1
	 */
	protected function create_uploaded_documents_action( Zaak $zaak, array $supplier_config ): AbstractCreateUploadedDocumentsAction
	{
		return new CreateUploadedDocumentsAction( $this->entry, $this->form, $supplier_config, $zaak );
	}
}
