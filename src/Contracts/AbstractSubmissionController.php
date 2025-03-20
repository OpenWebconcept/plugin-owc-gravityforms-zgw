<?php
/**
 * Abstract submission controller.
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

/**
 * Abstract submission controller.
 *
 * @since 1.0.0
 */
abstract class AbstractSubmissionController
{
	use FormSetting;

	abstract public function handle(array $validation_result ): array;

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
