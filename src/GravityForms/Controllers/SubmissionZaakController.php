<?php
/**
 * Submission controller.
 *
 * This controller is responsible for handling form submissions and delegates multiple actions towards the ZGW API.
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
use GFCommon;
use OWCGravityFormsZGW\Contracts\AbstractSubmissionController;
use OWC\ZGW\Entities\Zaak;

class SubmissionZaakController extends AbstractSubmissionController
{
	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected string $supplier_key;
	protected array $failed_messages = array();

	/**
	 * @since 1.0.0
	 */
	public function handle(array $validation_result ): array
	{
		$this->set_class_properties( $validation_result['form'] );

		if ( ! count( $this->entry )) {
			return $this->fail_form_validation(
				validation_result: $validation_result,
				failed_message: $this->failed_messages['zaak']
			);
		}

		try {
			$this->handle_zaak_creation();
			// $this->handle_zaak_uploads();
			// $this->handle_zaak_submission_pdf();
		} catch (Exception $e) {
			return $this->fail_form_validation(
				validation_result: $validation_result,
				failed_message: $e->getMessage()
			);
		}

		return $validation_result;
	}

	/**
	 * @since 1.0.0
	 */
	protected function set_class_properties(array $form ): void
	{
		$this->entry         = $this->create_entry_by_form( $form );
		$this->form          = $form;
		$this->supplier_name = $this->supplier_form_setting( form: $this->form, get_key: false );
		$this->supplier_key  = $this->supplier_form_setting( form: $this->form, get_key: true );

		$this->set_failed_messages_property();
	}

	/**
	 * @since 1.0.0
	 */
	protected function set_failed_messages_property(): void
	{
		$this->failed_messages = array(
			'zaak'           => __(
				'Er is een fout opgetreden bij het aanmaken van uw zaak. Probeer het later opnieuw.',
				'owc-gravityforms-zgw'
			),
			'uploads'        => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden aan uw zaak helaas niet.',
				'owc-gravityforms-zgw'
			),
			'submission_pdf' => __(
				'Het aanmaken van uw zaak is gelukt. Het genereren van de PDF helaas niet.',
				'owc-gravityforms-zgw'
			),
		);
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function handle_zaak_creation(): Zaak
	{
		$action = sprintf( 'OWCGravityFormsZGW\Clients\%s\Actions\CreateZaakAction', $this->supplier_name );

		if ( ! class_exists( $action )) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: class "%s" does not exists. Verify if the selected supplier has the required action class', $action ) );

			throw new Exception( $this->failed_messages['zaak'] );
		}

		try {
			return ( new $action( $this->entry, $this->form, $this->supplier_name, $this->supplier_key ) )->create();
		} catch (Exception $e) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: %s', $e->getMessage() ) );

			throw new Exception( $this->failed_messages['zaak'] );
		}
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function handle_zaak_uploads(): ?bool
	{
		$action = sprintf( 'OWCGravityFormsZGW\Clients\%s\Actions\CreateUploadedDocumentsAction', $this->supplier_name );

		if ( ! class_exists( $action )) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: class "%s" does not exists. Verify if the selected supplier has the required action class', $action ) );

			throw new Exception( $this->failed_messages['uploads'] );
		}

		try {
			return ( new $action() )->create( $this->entry, $this->form );
		} catch (Exception $e) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: %s', $e->getMessage() ) );

			throw new Exception( $this->failed_messages['uploads'] );
		}
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function handle_zaak_submission_pdf(): void
	{
		// @todo Implement.
	}
}
