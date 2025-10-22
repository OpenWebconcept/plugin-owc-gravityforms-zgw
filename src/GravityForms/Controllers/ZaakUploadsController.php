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

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Actions\CreateUploadedDocumentsAction;
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
	public function handle( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		try {
			$this->handle_zaak_uploads( $zaak, $supplier_name, $supplier_key );
		} catch (Throwable $e) {
			$message = sprintf(
				'Error while uploading attachments for Zaak "%s": %s',
				$zaak->getValue( 'identificatie', 'unknown' ),
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
	protected function handle_zaak_uploads( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		try {
			$action = new CreateUploadedDocumentsAction(
				$this->entry,
				$this->form,
				$supplier_name,
				$supplier_key,
				$zaak
			);

			$result = $action->add_uploaded_documents();

			if ($result === null) {
				throw new ZaakUploadException(
					sprintf(
						'No uploads were added to Zaak "%s". Action returned null.',
						$zaak->getValue( 'identificatie', 'unknown' )
					),
					400
				);
			}
		} catch ( Throwable $e ) {
			throw new ZaakUploadException(
				sprintf(
					'Failed adding uploads to Zaak "%s": %s',
					$zaak->getValue( 'identificatie', 'unknown' ),
					$e->getMessage()
				),
				400,
				$e
			);
		}
	}
}
