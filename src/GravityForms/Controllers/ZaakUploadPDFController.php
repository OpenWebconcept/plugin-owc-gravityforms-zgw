<?php
/**
 * Zaak upload PDF controller.
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
use OWC\ZGW\Entities\Zaakinformatieobject;
use OWCGravityFormsZGW\Actions\CreateSubmissionPDFAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use Throwable;

/**
 * Zaak upload PDF controller.
 *
 * @since 1.0.0
 */
class ZaakUploadPDFController extends AbstractZaakFormController
{
	/**
	 * Initialize Zaak PDF uploads and handle accordingly.
	 *
	 * @throws ZaakUploadException
	 */
	public function handle( Zaak $zaak, array $supplier_config ): void
	{
		try {
			$this->handle_zaak_pdf_uploads( $zaak, $supplier_config );
		} catch (Throwable $e) {
			$message = sprintf(
				'Error processing zaak PDF: %s: %s',
				$zaak->getValue( 'identificatie', 'unknown' ),
				$e->getMessage()
			);

			$this->logger->error( $message );

			throw new ZaakUploadException( $message, 400, $e );
		}
	}

	/**
	 * Add PDF uploads to Zaak using the supplier-specific Action class.
	 *
	 * @throws ZaakUploadException
	 */
	protected function handle_zaak_pdf_uploads( Zaak $zaak, array $supplier_config ): void
	{
		$action = new CreateSubmissionPDFAction(
			$this->entry,
			$this->form,
			$supplier_config,
			$zaak
		);

		$result = $action->add_submission_pdf();

		if ( ! $result instanceof Zaakinformatieobject) {
			throw new ZaakUploadException(
				sprintf(
					'Unexpected result type: %s',
					is_object( $result ) ? get_class( $result ) : gettype( $result )
				),
				400
			);
		}
	}
}
