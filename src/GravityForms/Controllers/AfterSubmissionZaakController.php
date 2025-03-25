<?php
/**
 * After Submission controller.
 *
 * This controller is responsible for handling form submissions after submitted
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

use Exception;
use GFCommon;
use OWCGravityFormsZGW\Traits\FormSetting;
use OWC\ZGW\Entities\Zaak;

/**
 * After Submission controller.
 *
 * @since 1.0.0
 */
class AfterSubmissionZaakController
{
	use FormSetting;

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected string $supplier_key;
	protected array $failed_messages = array();

	/**
	 * @since 1.0.0
	 */
	public function handle(array $entry, array $form ): array
	{
		// @todo validate if form is ZGW form.

		$this->set_class_properties( $entry, $form );

		try {
			$zaak = $this->restore_serialized_zaak_from_transient();

			$this->handle_zaak_uploads( $zaak );
			$this->handle_zaak_submission_pdf();
		} catch (Exception $e) {
			// @todo what to do when something fails overhere. Custom views are nasty. Let's try to edit a confirmation.
		}

		return $form;
	}

	/**
	 * @since 1.0.0
	 */
	protected function set_class_properties(array $entry, array $form ): void
	{
		$this->entry         = $entry;
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
			'transient'      => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden + de vereiste PDF konden helaas niet aan de zaak gekoppeld worden.', // @todo improve this text please.
				'owc-gravityforms-zgw'
			),
			'uploads'        => __(
				'Het aanmaken van uw zaak is gelukt. Het toevoegen van de geüploade bestanden aan uw zaak helaas niet.',
				'owc-gravityforms-zgw'
			),
			'submission_pdf' => __(
				'Het aanmaken van uw zaak is gelukt. Het genereren van de PDF helaas niet.', // @todo improve this text please.
				'owc-gravityforms-zgw'
			),
		);
	}

	/**
	 * Retrieves and unserializes the "zaak" object from the transient storage.
	 *
	 * This is used to access the previously stored Zaak instance,
	 * typically set during the validation phase (e.g. in the "gform_validation" hook),
	 * so it can be reused later in the submission flow.
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function restore_serialized_zaak_from_transient(): Zaak
	{
		$zaak = get_transient( sprintf( 'zgw_zaak_%s', md5( $this->entry['ip'] ) ) );

		if ( ! $zaak) {
			GFCommon::log_error( 'OWC_GravityForms_ZGW: unable to retrieve a "zaak" object from the transient storage' );

			throw new Exception( $this->failed_messages['transient'] );

		}

		$zaak = @unserialize( $zaak );

		if ( ! $zaak instanceof Zaak) {
			GFCommon::log_error( 'OWC_GravityForms_ZGW: unable to unserialize the "zaak" object retrieved from the transient storage' );

			throw new Exception( $this->failed_messages['transient'] );
		}

		return $zaak;
	}

	/**
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected function handle_zaak_uploads(Zaak $zaak ): ?bool
	{
		$action = sprintf( 'OWCGravityFormsZGW\Clients\%s\Actions\CreateUploadedDocumentsAction', $this->supplier_name );

		if ( ! class_exists( $action )) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: class "%s" does not exists. Verify if the selected supplier has the required action class', $action ) );

			throw new Exception( $this->failed_messages['uploads'] );
		}

		try {
			return ( new $action( $this->entry, $this->form, $this->supplier_name, $this->supplier_key, $zaak ) )->add_uploaded_documents();
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
