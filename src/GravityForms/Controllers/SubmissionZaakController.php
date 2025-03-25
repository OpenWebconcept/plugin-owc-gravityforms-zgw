<?php
/**
 * Submission controller.
 *
 * This controller is responsible for handling form submissions before submitted
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
use GFCommon;
use GFFormsModel;
use OWCGravityFormsZGW\Traits\FormSetting;
use OWC\ZGW\Entities\Zaak;

/**
 * Submission controller.
 *
 * @since 1.0.0
 */
class SubmissionZaakController
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
	public function handle(array $validation_result ): array
	{
		// @todo validate if form is ZGW form.

		$this->set_class_properties( $validation_result['form'] );

		if ( ! count( $this->entry )) {
			return $this->fail_form_validation(
				validation_result: $validation_result,
				failed_message: $this->failed_messages['zaak']
			);
		}

		try {
			$this->handle_zaak_creation();
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
	 * If there is no entry array available, create one based on the form array.
	 * Useful in hooks where the entry has not been created yet, such as 'gform_validation'.
	 *
	 * @since 1.0.0
	 */
	public function create_entry_by_form(array $form ): array
	{
		try {
			$entry = GFFormsModel::create_lead( $form );
		} catch (Exception $e) {
			$entry = null;
		}

		return is_array( $entry ) ? $entry : array();
	}

	/**
	 * @since 1.0.0
	 */
	protected function set_failed_messages_property(): void
	{
		$this->failed_messages = array(
			'zaak' => __(
				'Er is een fout opgetreden bij het aanmaken van uw zaak. Probeer het later opnieuw.',
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
			$zaak = ( new $action( $this->entry, $this->form, $this->supplier_name, $this->supplier_key ) )->create();
		} catch (Exception $e) {
			GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: %s', $e->getMessage() ) );

			throw new Exception( $this->failed_messages['zaak'] );
		}

		$this->store_serialized_zaak_in_transient( $zaak );

		return $zaak;
	}

	/**
	 * Stores the serialized "Zaak" object in a transient for later use,
	 * for example in the "gform_after_submission" hook.
	 *
	 * The "gform_after_submission" hook handles uploaded documents and the submission PDF.
	 */
	protected function store_serialized_zaak_in_transient(Zaak $zaak ): void
	{
		set_transient( sprintf( 'zgw_zaak_%s', md5( $this->entry['ip'] ) ), serialize( $zaak ), 60 );
	}

	/**
	 * Fail the form validation with a message.
	 * Make sure this method is called inside the 'gform_validation' hook.
	 *
	 * @since 1.0.0
	 */
	public function fail_form_validation(array $validation_result, string $failed_message ): array
	{
		$validation_result['is_valid'] = false;

		add_filter(
			'gform_validation_message',
			function ($message ) use ( $failed_message ) {
				return $failed_message;
			},
			10,
			1
		);

		return $validation_result;
	}
}
