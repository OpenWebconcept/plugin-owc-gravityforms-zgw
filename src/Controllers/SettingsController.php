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

	public function section_description_general(): void
	{
		owc_gravityforms_zgw_render_view( 'admin/partials/settings/settings-description-general' );
	}

	public function section_fields_render( array $args ): void
	{
		owc_gravityforms_zgw_render_view(
			'admin/partials/settings/settings-fields',
			array(
				'settings_field_id'                 => $args['settings_field_id'] ?? '',
				'available_user_roles'              => get_editable_roles(),
				'owc_gf_zgw_transaction_user_roles' => ContainerResolver::make()->get( 'zgw.site_options' )->transaction_user_roles(),
			)
		);
	}

	public function sanitize_plugin_options_settings( $settings ): array
	{
		if ( ! is_array( $settings ) ) {
			return array();
		}

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
}
