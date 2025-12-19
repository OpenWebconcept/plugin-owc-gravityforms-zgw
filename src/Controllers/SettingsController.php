<?php
/**
 * Settings Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\ContainerResolver;

/**
 * @since 1.1.0
 */
class SettingsController
{
	public function render_page(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/settings-page' );
	}

	public function section_description_transactions_overview(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description_transactions_overview' );
	}

	public function section_description_transactions_report(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description_transactions_report' );
	}

	public function section_fields_render( array $args ): void
	{
		owc_gravityforms_zgw_render_view(
			'admin/partials/settings/settings-fields',
			array(
				'settings_field_id'                 => $args['settings_field_id'] ?? '',
				'available_user_roles'              => get_editable_roles(),
				'owc_gf_zgw_transaction_user_roles' => ContainerResolver::make()->get( 'zgw.site_options' )->transaction_user_roles(),
				'owc_zgw_transactions_report_recipient_email' => ContainerResolver::make()->get( 'zgw.site_options' )->transaction_report_recipient_email(),
			)
		);
	}

	public function sanitize_validate_plugin_options_settings( $settings ): array
	{
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$settings = $this->sanitize_plugin_options_settings( $settings );
		$settings = $this->validate_plugin_options_settings( $settings );

		return $settings;
	}

	/**
	 * @since 1.1.4
	 */
	private function sanitize_plugin_options_settings( $settings ): array
	{
		$sanitize_recursive = function ( $value ) use ( &$sanitize_recursive ) {
			if ( is_array( $value ) ) {
				return array_map( $sanitize_recursive, $value );
			}

			if ( is_string( $value ) ) {
				return sanitize_text_field( $value );
			}

			return $value;
		};

		return array_map( $sanitize_recursive, $settings );
	}

	/**
	 * @since 1.1.4
	 */
	private function validate_plugin_options_settings( $settings ): array
	{
		$settings['owc_zgw_transactions_report_recipient_email'] = $this->validate_email( $settings['owc_zgw_transactions_report_recipient_email'] ?? '' );

		return $settings;
	}

	/**
	 * @since 1.1.4
	 */
	private function validate_email( $email ): string
	{
		if ( ! is_email( $email ) ) {
			add_settings_error(
				'owc_gf_zgw_options_group',
				'owc_gf_zgw_invalid_email',
				__( 'Ongeldig e-mailadres voor het verzenden van het transactie rapport.', 'owc-gravityforms-zgw' ),
				'error'
			);

			return '';
		}

		return $email;
	}
}
