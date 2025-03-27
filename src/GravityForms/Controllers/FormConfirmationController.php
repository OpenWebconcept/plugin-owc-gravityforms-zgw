<?php
/**
 * Form confirmation controller.
 *
 * This controller is responsible for handling the form confirmation after the actions
 * towards the ZGW API are finished.
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

use GFFormsModel;

/**
 * Form confirmation controller.
 *
 * @since 1.0.0
 */
class FormConfirmationController
{
	/**
	 * @since 1.0.0
	 */
	public function handle(mixed $confirmation, array $form, array $entry )
	{
		$transient_key = sprintf( '%s_%s', OWC_GRAVITYFORMS_ZGW_TRANSIENT_KEY_FAILED_SUBMISSION, md5( $entry['ip'] ) );

		if ($transient_value = get_transient( $transient_key ) ) {
			delete_transient( $transient_key );

			GFFormsModel::add_note(
				$entry['id'],
				0,
				'OWC_GravityForms_ZGW',
				$transient_value
			);

			return $transient_value;
		}

		return $confirmation;
	}
}
