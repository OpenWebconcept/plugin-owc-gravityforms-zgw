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

use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Actions\CreateZaakAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\Exceptions\ZaakException;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use Throwable;

/**
 * Zaak controller.
 *
 * @since 1.0.0
 */
class ZaakController extends AbstractZaakFormController
{
	private ZaakUploadsController $uploads_controller;
	private ZaakUploadPDFController $upload_pdf_controller;

	public function __construct(
		?ZaakUploadsController $uploads_controller = null,
		?ZaakUploadPDFController $upload_pdf_controller = null
	) {
		parent::__construct();
		$this->uploads_controller    = $uploads_controller ?? new ZaakUploadsController();
		$this->upload_pdf_controller = $upload_pdf_controller ?? new ZaakUploadPDFController();
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
			$zaak = $this->create_zaak( $supplier_name, $supplier_key );
			$this->handle_uploads( $zaak, $supplier_name, $supplier_key );

			$this->mark_transaction_success( $zaak );
		} catch (ZaakException $e) {
			$this->mark_transaction_failed( $e->getMessage() );
		} catch (Throwable $e) {
			$this->mark_transaction_failed(
				( new ZaakException( $e->getMessage(), 500, $e ) )->getMessage()
			);
		}
	}

	/**
	 * Create the Zaak using the supplier-specific Action class.
	 *
	 * @throws ZaakException
	 */
	protected function create_zaak( string $supplier_name, string $supplier_key ): Zaak
	{
		try {
			$action = ( new CreateZaakAction(
				$this->entry,
				$this->form,
				$supplier_name,
				$supplier_key
			) );

			$zaak = $action->create();
			$this->add_zaak_reference_to_post( $zaak );

			return $zaak;
		} catch ( Throwable $e ) {
			throw new ZaakException(
				sprintf( 'OWC_GravityForms_ZGW: %s', $e->getMessage() ),
				400,
				$e
			);
		}
	}

	/**
	 * Handle both file and PDF uploads.
	 *
	 * @throws ZaakUploadException
	 */
	protected function handle_uploads(Zaak $zaak, string $supplier_name, string $supplier_key ): void
	{
		try {
			// Handle normal uploads.
			$this->uploads_controller->set_form_data( $this->entry, $this->form );
			$this->uploads_controller->handle( $zaak, $supplier_name, $supplier_key );

			// Handle PDF uploads.
			$this->upload_pdf_controller->set_form_data( $this->entry, $this->form );
			$this->upload_pdf_controller->handle( $zaak, $supplier_name, $supplier_key );
		} catch (Throwable $e) {
			throw new ZaakUploadException( $e->getMessage() );
		}
	}
}
