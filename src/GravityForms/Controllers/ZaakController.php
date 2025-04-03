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
use GFCommon;
use OWCGravityFormsZGW\Contracts\AbstractZaakFormController;
use OWC\ZGW\Entities\Zaak;

/**
 * Zaak controller.
 *
 * @since 1.0.0
 */
class ZaakController extends AbstractZaakFormController
{
	/**
	 * @since 1.0.0
	 */
	public function handle(array $validation_result ): array
	{
		$this->set_class_properties( $validation_result['form'] );

		if ( ! $this->form_is_zgw()) {
			return $validation_result;
		}

		try {
			if ( ! count( $this->entry )) {
				throw new Exception( $this->failed_messages['zaak'], 500 );
			}

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
	protected function set_failed_messages_property(): array
	{
		return array(
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
			$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: class "%s" does not exists. Verify if the selected supplier has the required action class', $action ) );

			throw new Exception( $this->failed_messages['zaak'], 500 );
		}

		try {
			$zaak = ( new $action( $this->entry, $this->form, $this->supplier_name, $this->supplier_key ) )->create();
		} catch (Exception $e) {
			$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: %s', $e->getMessage() ) );

			throw new Exception( $this->failed_messages['zaak'], 400 );
		}

		$this->store_serialized_zaak_in_transient( $zaak );

		return $zaak;
	}

	/**
	 * Stores the serialized "Zaak" object in a transient for later use,
	 * for example in the "gform_after_submission" hook.
	 *
	 * The "gform_after_submission" hook handles uploaded documents and the submission PDF.
	 *
	 * @since 1.0.0
	 */
	protected function store_serialized_zaak_in_transient(Zaak $zaak ): void
	{
		set_transient( sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_CREATED_ZAAK, md5( $this->entry['ip'] ) ), serialize( $zaak ), 60 );
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
