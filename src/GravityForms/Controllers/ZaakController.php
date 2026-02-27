<?php

declare(strict_types=1);

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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Actions\CreateZaakAction;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWCGravityFormsZGW\Exceptions\ZaakException;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWC\ZGW\Entities\Zaak;
use Throwable;
use OWCGravityFormsZGW\ContainerResolver;

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
	public function handle( array $entry, array $form ): ?Zaak
	{
		// Only handle zaak creation for ZGW enabled forms.
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return null;
		}

		// Make entry/form available to other methods that expect them.
		$this->entry = $entry;
		$this->form  = $form;

		// Get the supplier name and key from form settings.
		$supplier_config = FormUtils::get_supplier_config( $form );

		$zaak      = null;
		$is_failed = false;

		// Create the Zaak.
		try {
			$zaak = $this->create_zaak( $supplier_config );
			$this->handle_uploads( $zaak, $supplier_config );

			$this->mark_transaction_success( $zaak );
		} catch ( ZaakException $e ) {
			$this->mark_transaction_failed( $e->getMessage() );

			$is_failed = true;
		} catch ( Throwable $e ) {
			$this->mark_transaction_failed(
				( new ZaakException( $e->getMessage(), 500, $e ) )->getMessage()
			);

			$is_failed = true;
		}

		if ( $is_failed && $zaak instanceof Zaak ) {
			$zaak->setValue( 'creation_failed', true ); // Value is used by the retry mechanism.
		}

		return $zaak instanceof Zaak ? $zaak : null;
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

			$this->delegate_delay( $supplier_config );

			return $zaak;
		} catch ( Throwable $e ) {
			$reason_message = $this->extract_api_error_message( $e );

			throw new ZaakException(
				$reason_message,
				400,
				$e
			);
		}
	}

	/**
	 * Delegates a delay after zaak creation to allow supplier-side post-processing
	 * to complete before attempting to upload documents.
	 *
	 * After a zaak is created, certain suppliers execute additional asynchronous
	 * processes (e.g. registration, synchronization, authorization propagation or
	 * internal indexing). During this short window, follow-up operations such as
	 * document uploads may fail because the zaak is not yet fully ready for mutation.
	 *
	 * The delay duration is determined by the
	 * 'owc_zgw_delay_after_zaak_creation_seconds' site option and defaults to 0
	 * seconds if not set or if an invalid value is provided.
	 *
	 * NOTE:
	 * This delay is a pragmatic mitigation for supplier-specific timing constraints.
	 * Ideally, the supplier API should expose a reliable readiness signal or guarantee
	 * synchronous consistency. This workaround can be removed once such guarantees
	 * are in place.
	 */
	private function delegate_delay(array $supplier_config ): void
	{
		$selected_suppliers = ContainerResolver::make()->get( 'zgw.site_options' )->delay_after_zaak_creation_suppliers();

		if ( ! in_array( $supplier_config['client_type'], $selected_suppliers, true ) ) {
			return;
		}

		$delay = ContainerResolver::make()->get( 'zgw.site_options' )->delay_after_zaak_creation_seconds();

		sleep( $delay );
	}

	/**
	 * Handle both file and PDF uploads.
	 *
	 * @throws ZaakUploadException
	 */
	protected function handle_uploads(Zaak $zaak, array $supplier_config ): void
	{
		$caught_exception = null;

		// Handle user submitted file uploads.
		try {
			$this->uploads_controller->set_form_data( $this->entry, $this->form );
			$this->uploads_controller->handle( $zaak, $supplier_config );
		} catch ( Throwable $e ) {
			$this->logger->error( 'File uploads failed: ' . $e->getMessage() );
			$caught_exception = $e;
		}

		// Handle adding the generated PDF upload.
		try {
			$this->upload_pdf_controller->set_form_data( $this->entry, $this->form );
			$this->upload_pdf_controller->handle( $zaak, $supplier_config );
		} catch ( Throwable $e ) {
			$this->logger->error( 'PDF generation failed: ' . $e->getMessage() );
			$caught_exception = $e;
		}

		if ( $caught_exception ) {
			throw new ZaakUploadException(
				$caught_exception->getMessage(),
				400
			);
		}
	}
}
