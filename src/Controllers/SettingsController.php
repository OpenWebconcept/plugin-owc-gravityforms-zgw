<?php

declare(strict_types=1);

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
		if ( current_user_can( 'manage_options' ) ) {
			owc_gravityforms_zgw_render_view( 'admin/settings-page' );

			return;
		}

		/**
		 * Users who only have the filtered "clear cache" capability, and not 'manage_options',
		 * get a minimal page containing just the cache section instead of the full settings
		 * form. This avoids exposing the other, admin-only settings to them and prevents them
		 * from accidentally overwriting those settings by submitting the form.
		 *
		 * @since NEXT
		 */
		owc_gravityforms_zgw_render_view(
			'admin/settings-page-cache-only',
			array(
				'cache_controller' => new FormSettingsCacheController(),
			)
		);
	}

	public function section_description_transactions_overview(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description-transactions-overview' );
	}

	public function section_description_transactions_report(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description-transactions-report' );
	}

	/**
	 * @since 1.6.0
	 */
	public function section_description_delay_after_zaak_creation(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description-delay-after-zaak-creation' );
	}

	/**
	 * @since 1.7.0
	 */
	public function description_client_request_timeout_option(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/description-client-request-timeout-option' );
	}

	/**
	 * @since NEXT
	 */
	public function section_description_form_settings_cache(): void
	{
		owc_gravityforms_zgw_render_view(
			'admin/partials/settings/description-form-settings-cache',
			array(
				'cache_controller' => new FormSettingsCacheController(),
			)
		);
	}

	public function section_fields_render( array $args ): void
	{
		owc_gravityforms_zgw_render_view(
			'admin/partials/settings/settings-fields',
			array(
				'settings_field_id'                     => $args['settings_field_id'] ?? '',
				'available_user_roles'                  => get_editable_roles(),
				'selectable_suppliers'                  => ContainerResolver::make()->get( 'suppliers' ),
				'owc_gf_zgw_transaction_user_roles'     => ContainerResolver::make()->get( 'zgw.site_options' )->transaction_user_roles(),
				'owc_zgw_transactions_report_recipient_email' => ContainerResolver::make()->get( 'zgw.site_options' )->transaction_report_recipient_email(),
				'owc_zgw_delay_after_zaak_creation_seconds' => ContainerResolver::make()->get( 'zgw.site_options' )->delay_after_zaak_creation_seconds(),
				'owc_zgw_delay_after_zaak_creation_suppliers' => ContainerResolver::make()->get( 'zgw.site_options' )->delay_after_zaak_creation_suppliers(),
				'owc_zgw_client_request_timeout_option' => ContainerResolver::make()->get( 'zgw.site_options' )->client_request_timeout_option(),
				'owc_zgw_client_request_timeout_option_suppliers' => ContainerResolver::make()->get( 'zgw.site_options' )->client_request_timeout_option_suppliers(),
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
