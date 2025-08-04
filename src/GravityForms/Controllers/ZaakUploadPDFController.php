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

use Exception;
use OWCGravityFormsZGW\Actions\CreateSubmissionPDFAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakinformatieobject;

/**
 * Zaak upload PDF controller.
 *
 * @since 1.0.0
 */
class ZaakUploadPDFController extends AbstractZaakFormController
{
	/**
	 * Init Zaak upload PDFs and handle accordingly.
	 *
	 * @throws Exception
	 */
	public function handle( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		$this->handle_zaak_pdf_uploads( $zaak, $supplier_name, $supplier_key );
	}

	/**
	 * Add PDF uploads to Zaak using the supplier-specific Action class.
	 */
	protected function handle_zaak_pdf_uploads( Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
        $action = ( new CreateSubmissionPDFAction(
            $this->entry,
            $this->form,
            $supplier_name,
            $supplier_key,
            $zaak
        ) );

        $result = $action->add_submission_pdf();

		if ( ! $result instanceof Zaakinformatieobject) {
			throw new Exception(
				sprintf(
					'Failed adding PDF uploads to "%s"',
					$zaak->getValue( 'identificatie', '' )
				),
				400
			);
		}
	}
}
