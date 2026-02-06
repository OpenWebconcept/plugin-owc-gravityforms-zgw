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

use OWCGravityFormsZGW\Actions\CreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use OWC\ZGW\Entities\Zaak;
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
		try {
			$action = new CreateUploadedDocumentsAction(
				$this->entry,
				$this->form,
				$supplier_config,
				$zaak
			);

			$result = $action->add_uploaded_documents();

			if ( $result === false ) {
				throw new ZaakUploadException(
					sprintf(
						'Not all uploads were successfully added to zaak %s.',
						$zaak->getValue( 'identificatie', 'unknown' )
					),
					400
				);
			}

			// $result === null → no uploads mapped, skip silently
			// $result === true → all uploads succeeded
		} catch ( Throwable $e ) {
			$reason_message = $this->extract_api_error_message( $e );

			throw new ZaakUploadException(
				$reason_message,
				400,
				$e
			);
		}
	}
}
