<?php

declare(strict_types=1);

/**
 * Identification validation controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Auth\DigiD;
use OWCGravityFormsZGW\Auth\eHerkenning;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Blocks the final submission of a ZGW form when no BSN or KVK is available.
 *
 * Creating the "rol" of a "zaak" requires a BSN or KVK. This identification is read from
 * the DigiD/eHerkenning session when the form is submitted, the "zaak" itself is created
 * later in a background process. Login fields usually guard the session, but are skipped
 * during validation when they are hidden, for example by conditional logic depending on
 * values posted by the browser. Validating the identification here, independent of the
 * fields on the form, prevents a submission (and a "zaak") without an initiator.
 *
 * @since NEXT
 */
class IdentificationValidationController
{
	/**
	 * IDs of the forms that failed validation because of missing identification during this request.
	 *
	 * @var int[]
	 */
	private array $failed_form_ids = array();

	/**
	 * Hooked to 'gform_validation'.
	 */
	public function validate( array $validation_result ): array
	{
		$form = $validation_result['form'] ?? array();

		if ( ! is_array( $form ) || ! $this->is_final_submission( $form ) || ! $this->is_form_zgw( $form ) ) {
			return $validation_result;
		}

		if ( $this->has_identification( $form ) ) {
			return $validation_result;
		}

		$this->failed_form_ids[] = (int) ( $form['id'] ?? 0 );

		$validation_result['is_valid'] = false;

		return $validation_result;
	}

	/**
	 * Hooked to 'gform_validation_message', replaces the generic message when identification is missing.
	 */
	public function validation_message( string $message, array $form ): string
	{
		if ( ! in_array( (int) ( $form['id'] ?? 0 ), $this->failed_form_ids, true ) ) {
			return $message;
		}

		return sprintf(
			'<h2 class="gform_submission_error hide_summary">%s</h2>',
			esc_html__( 'Je bent niet (meer) ingelogd. Log opnieuw in met DigiD of eHerkenning om het formulier te versturen.', 'owc-gravityforms-zgw' )
		);
	}

	protected function is_form_zgw( array $form ): bool
	{
		return FormUtils::is_form_zgw( $form );
	}

	/**
	 * Uses the same sources as the transaction post, which the "zaak" creation reads the identification from.
	 *
	 * @see \OWCGravityFormsZGW\Transactions\Controllers\TransactionController::add_metadata()
	 */
	protected function has_identification( array $form ): bool
	{
		if ( null !== FormUtils::overwrite_bsn_form_setting( $form ) || null !== FormUtils::overwrite_kvk_form_setting( $form ) ) {
			return true;
		}

		return '' !== DigiD::make()->bsn() || '' !== eHerkenning::make()->kvk();
	}

	/**
	 * Multi-page forms are validated on every page change, only the final submission (target page 0) requires identification.
	 */
	protected function is_final_submission( array $form ): bool
	{
		$target_page = rgpost( sprintf( 'gform_target_page_number_%d', (int) ( $form['id'] ?? 0 ) ) );

		return '' === (string) $target_page || 0 === (int) $target_page;
	}
}
