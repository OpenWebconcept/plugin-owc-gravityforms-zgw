<?php
/**
 * Abstract zaak form controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Contracts;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Exception;
use GFFormsModel;
use OWCGravityFormsZGW\Traits\FormSetting;
use OWC\ZGW\Entities\Zaak;

/**
 * Abstract zaak form controller.
 *
 * @since 1.0.0
 */
abstract class AbstractZaakFormController
{
	use FormSetting;

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected string $supplier_key;
	protected array $failed_messages = array();

	public function __construct()
	{
		$this->failed_messages = $this->set_failed_messages_property();
	}

	/**
	 * @since 1.0.0
	 */
	protected function set_class_properties(array $form, array $entry = array() ): void
	{
		$this->entry         = $entry ? $entry : $this->create_entry_by_form( $form );
		$this->form          = $form;
		$this->supplier_name = $this->supplier_form_setting( form: $this->form, get_key: false );
		$this->supplier_key  = $this->supplier_form_setting( form: $this->form, get_key: true );
	}

	/**
	 * @since 1.0.0
	 */
	protected function create_entry_by_form(array $form ): array
	{
		try {
			$entry = GFFormsModel::create_lead( $form );
		} catch (Exception $e) {
			$entry = null;
		}

		return is_array( $entry ) ? $entry : array();
	}

	/**
	 * Validate if the current form is connected to ZGW.
	 *
	 * @since 1.0.0
	 */
	protected function form_is_zgw(): bool
	{
		return 0 < strlen( $this->supplier_name ) || 0 < strlen( $this->supplier_key );
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
	protected function restore_serialized_zaak_from_transient(string $failed_message_type, bool $delete_transient = false ): Zaak
	{
		$zaak = get_transient( sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_CREATED_ZAAK, md5( $this->entry['ip'] ) ) );

		if ( ! $zaak) {
			GFCommon::log_error( 'OWC_GravityForms_ZGW: unable to retrieve a "zaak" object from the transient storage' );

			throw new Exception( $this->failed_messages[ $failed_message_type ] );

		}

		$zaak = @unserialize( $zaak );

		if ( ! $zaak instanceof Zaak) {
			GFCommon::log_error( 'OWC_GravityForms_ZGW: unable to unserialize the "zaak" object retrieved from the transient storage' );

			// After unsuccessful retrieval, remove the transient.
			delete_transient( sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_CREATED_ZAAK, md5( $this->entry['ip'] ) ) );

			throw new Exception( $this->failed_messages[ $failed_message_type ] );
		}

		// After successful retrieval, remove the transient if needed.
		if ($delete_transient) {
			delete_transient( sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_CREATED_ZAAK, md5( $this->entry['ip'] ) ) );
		}

		return $zaak;
	}

	/**
	 * @since 1.0.0
	 */
	protected function validate_action_class(string $action, string $failed_message_type ): void
	{
		if (class_exists( $action )) {
			return;
		}

		GFCommon::log_error( sprintf( 'OWC_GravityForms_ZGW: class "%s" does not exists. Verify if the selected supplier has the required action class', $action ) );

		throw new Exception( $this->failed_messages[ $failed_message_type ] );
	}

	/**
	 * @since 1.0.0
	 */
	abstract protected function set_failed_messages_property(): array;
}
