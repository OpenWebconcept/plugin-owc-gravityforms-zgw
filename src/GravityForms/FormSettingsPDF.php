<?php

declare(strict_types=1);

/**
 * Form settings PDF.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GPDFAPI;

/**
 * Form settings PDF.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
class FormSettingsPDF
{
	protected array $entry;
	protected array $form;

	public function __construct( array $entry, array $form )
	{
		$this->entry = $entry;
		$this->form  = $form;
	}

	/**
	 * Get the first ID of the PDF settings configured per form.
	 */
	public function pdf_form_setting_id(): string
	{
		$settings = array_keys( $this->get_form_settings_pdf( array() ) );

		return $settings[0] ?? '';
	}

	protected function get_form_settings_pdf( $default = null ): mixed
	{
		if ( ! is_array( $this->form['gfpdf_form_settings'] ?? false ) || array() === $this->form['gfpdf_form_settings'] ) {
			return $default;
		}

		return array_filter(
			$this->form['gfpdf_form_settings'],
			function ( $form_settings_pdf ) {
				return ! empty( $form_settings_pdf['active'] );
			}
		);
	}

	public function pdf_form_setting_is_active(): bool
	{
		$settings = $this->get_form_settings_pdf();

		if ( ! is_array( $settings ) || array() === $settings ) {
			return false;
		}

		$settings = reset( $settings );

		return $settings['active'] ?? false;
	}

	/**
	 * Generate a fresh PDF for the current entry and return its absolute file path.
	 *
	 * Using GPDFAPI::create_pdf() re-fetches the entry from the database, ensuring
	 * any metadata added after submission (e.g. the zaak UUID) is available to the
	 * PDF template's merge tags. It also bypasses any cached version.
	 */
	public function generate_pdf_path(): string
	{
		$setting_id = $this->pdf_form_setting_id();

		if ( empty( $setting_id ) ) {
			return '';
		}

		$pdf_path = GPDFAPI::create_pdf( $this->entry['id'], $setting_id );

		if ( is_wp_error( $pdf_path ) || ! is_string( $pdf_path ) || ! is_file( $pdf_path ) ) {
			return '';
		}

		return $pdf_path;
	}
}
