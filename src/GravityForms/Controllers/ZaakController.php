<?php
/**
 * Zaak controller.
 *
 * This controller is responsible for handling form submissions after the form validation
 * and delegates the create action towards the ZGW API.
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
use Throwable;
use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Actions\CreateZaakAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Zaak controller.
 *
 * @since 1.0.0
 */
class ZaakController extends AbstractZaakFormController
{
	private ZaakUploadsController $uploads_controller;
	private ZaakUploadPDFController $upload_pdf_controller;

	public function __construct()
	{
		parent::__construct();
		$this->uploads_controller    = new ZaakUploadsController();
		$this->upload_pdf_controller = new ZaakUploadPDFController();
	}

	/**
	 * Init Zaak creation and handle accordingly.
	 */
	public function handle( array $entry, array $form ): void
	{
		// Only create transaction for ZGW enabled forms.
		if ( ! FormUtils::form_is_zgw( $form ) ) {
			return;
		}

		// Make entry/form available to other methods that expect them.
		$this->entry = $entry;
		$this->form  = $form;

		// Get the supplier name and key from form settings.
		$supplier_name = FormUtils::supplier_form_setting( $form );
		$supplier_key  = FormUtils::supplier_form_setting( $form, true );

		try {
			// Create the Zaak.
			$zaak = $this->handle_zaak_creation( $supplier_name, $supplier_key );

			// Call the upload handlers to attach files to the Zaak.
			$this->uploads_controller->set_form_data( $entry, $form );
			$this->uploads_controller->handle( $zaak, $supplier_name, $supplier_key );

			// Call the PDF upload handler to attach documents to the Zaak.
			$this->upload_pdf_controller->set_form_data( $entry, $form );
			$this->upload_pdf_controller->handle( $zaak, $supplier_name, $supplier_key );

			$this->mark_transaction_success( $zaak );
		} catch ( Throwable $e ) {
			$this->mark_transaction_failed( $e->getMessage() );
		}
	}

	/**
	 * Create the Zaak using the supplier-specific Action class.
	 *
	 * @throws Exception
	 */
	protected function handle_zaak_creation( string $supplier_name, string $supplier_key ): Zaak
	{
		try {
			$action = ( new CreateZaakAction(
				$this->entry,
				$this->form,
				$supplier_name,
				$supplier_key
			) );

			$result = $action->create();

			$this->add_zaak_reference_to_post( $result );
		} catch ( Throwable $e ) {
			throw new Exception(
				sprintf(
					'OWC_GravityForms_ZGW: %s',
					$e->getMessage()
				),
				400,
				$e
			);
		}

		return $result;
	}
}
