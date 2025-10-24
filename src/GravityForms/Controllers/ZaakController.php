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
	 * Initialize Zaak creation and handle accordingly.
	 */
	public function handle( array $entry, array $form ): void
	{
		// Only handle zaak creation for ZGW enabled forms.
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return;
		}

		// Make entry/form available to other methods that expect them.
		$this->entry = $entry;
		$this->form  = $form;

		// Get the supplier name and key from form settings.
		$supplier_config = FormUtils::get_supplier_config( $form );

		try {
			// Create the Zaak.
			$zaak = $this->create_zaak( $supplier_config );
			$this->handle_uploads( $zaak, $supplier_config );

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
	protected function create_zaak( array $supplier_config ): Zaak
	{
		try {
			$action = ( new CreateZaakAction(
				$this->entry,
				$this->form,
				$supplier_config
			) );

			$zaak = $action->create();
			$this->add_zaak_reference_to_post( $zaak );

			return $zaak;
		} catch ( Throwable $e ) {
			$reasonMessage = $this->extractApiErrorMessage( $e );

			throw new ZaakException(
				$reasonMessage,
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
	protected function handle_uploads(Zaak $zaak, array $supplier_config ): void
	{
		$caughtException = null;

		// Handle user submitted file uploads.
		try {
			$this->uploads_controller->set_form_data( $this->entry, $this->form );
			$this->uploads_controller->handle( $zaak, $supplier_config );
		} catch (Throwable $e) {
			$this->logger->error( 'File uploads failed: ' . $e->getMessage() );
			$caughtException = $e;
		}

		// Handle adding the generated PDF upload.
		try {
			$this->upload_pdf_controller->set_form_data( $this->entry, $this->form );
			$this->upload_pdf_controller->handle( $zaak, $supplier_config );
		} catch (Throwable $e) {
			$this->logger->error( 'PDF upload failed: ' . $e->getMessage() );
			$caughtException = $e;
		}

		if ($caughtException) {
			throw new ZaakUploadException(
				$caughtException->getMessage(),
				400,
				$caughtException
			);
		}
	}
}
