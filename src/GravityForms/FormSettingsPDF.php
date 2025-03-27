<?php
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
if ( ! defined( 'ABSPATH' )) {
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

	public function __construct(array $entry, array $form )
	{
		$this->entry = $entry;
		$this->form  = $form;
	}

	/**
	 * Get the first ID of the PDF settings configured per form.
	 *
	 * @since 1.0.0
	 */
	public function pdf_form_setting_id(): string
	{
		$settings = array_keys( $this->get_form_settings_pdf( array() ) );

		return $settings[0] ?? '';
	}

	/**
	 * @since 1.0.0
	 */
	protected function get_form_settings_pdf($default = null ): mixed
	{
		if (empty( $this->form['gfpdf_form_settings'] ) || ! is_array( $this->form['gfpdf_form_settings'] )) {
			return $default;
		}

		return array_filter(
			$this->form['gfpdf_form_settings'],
			function ($form_settings_pdf ) {
				return ! empty( $form_settings_pdf['active'] );
			}
		);
	}

	/**
	 * @since 1.0.0
	 */
	public function pdf_form_setting_is_active(): bool
	{
		$settings = $this->get_form_settings_pdf();

		if (empty( $settings )) {
			return false;
		}

		$settings = reset( $settings );

		return $settings['active'] ?? false;
	}

	/**
	 * @since 1.0.0
	 */
	public function url_pdf(): string
	{
		$setting_id = $this->pdf_form_setting_id();

		if (empty( $setting_id )) {
			return '';
		}

		$pdf_model = GPDFAPI::get_pdf_class( 'model' );

		if (is_wp_error( $pdf_model )) {
			return '';
		}

		return $pdf_model->get_pdf_url( $setting_id, $this->entry['id'] );
	}

	/**
	 * Toggles the "public_access" setting for the generated PDFs.
	 * By default, the PDFs are protected and not publicly accessible.
	 *
	 * @since 1.0.0
	 */
	public function update_public_access_setting_pdf(string $access = '' ): bool
	{
		$setting_id = $this->pdf_form_setting_id();
		$settings   = GPDFAPI::get_pdf( $this->form['id'], $setting_id );

		if ( ! is_array( $settings )) {
			return false;
		}

		$settings['public_access'] = 'enable' === $access ? 'Yes' : '';

		return GPDFAPI::update_pdf( $this->form['id'], $setting_id, $settings );
	}
}
