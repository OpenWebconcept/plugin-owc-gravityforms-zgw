<?php

declare(strict_types=1);

/**
 * Form Utils.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms;

use GFAPI;
use OWCGravityFormsZGW\ContainerResolver;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Utils.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */
class FormUtils
{
	/**
	 * Get the supplier configured in the form settings.
	 */
	public static function get_supplier_config(array $form ): array
	{
		$supplier = $form[ sprintf( '%s-form-setting-supplier', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '';

		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		foreach ( $clients as $client_config ) {
			if ( strtolower( $client_config['name'] ) === $supplier ) {
				return $client_config;
			}
		}

		return array();
	}

	/**
	 * Check if the form is configured for ZGW.
	 */
	public static function is_form_zgw( array $form ): bool
	{
		$supplier_setting = self::get_supplier_config( form: $form );

		if ( empty( $supplier_setting ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get a link to the Gravity Forms entry in the admin.
	 */
	public static function get_link_to_form_entry( int $entry_id ): string
	{
		if ( $entry_id && class_exists( 'GFAPI' ) ) {
			$entry = GFAPI::get_entry( $entry_id );

			if ( ! is_wp_error( $entry ) ) {
				$url = add_query_arg(
					array(
						'page' => 'gf_entries',
						'view' => 'entry',
						'id'   => $entry['form_id'],
						'lid'  => $entry_id,
					),
					admin_url( 'admin.php' )
				);

				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( $url ),
					esc_html( $entry_id )
				);
			}
		}

		return '';
	}

	/**
	 * Checks if the form setting is selected or configured manually.
	 * Returns the selected zaaktype identifier.
	 */
	public static function zaaktype_identifier_form_setting(array $form, string $supplier_name ): string
	{
		$supplier_name = strtolower( $supplier_name );

		if ( '1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' ) ) {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-identifier-manual', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		} else {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-identifier', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		}

		return ! empty( $zaaktype_identifier ) ? $zaaktype_identifier : '';
	}

	/**
	 * Checks if the form setting is selected or configured manually.
	 * Returns the selected information object type identifier.
	 */
	public static function information_object_type_form_setting(array $form, string $supplier_name ): string
	{
		$supplier_name = strtolower( $supplier_name );

		if ( '1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' ) ) {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-information-object-type-manual', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		} else {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-information-object-type', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		}

		return ! empty( $zaaktype_identifier ) ? $zaaktype_identifier : '';
	}

	/**
	 * Return the value of the overwrite BSN form setting.
	 *
	 * @since 1.1.0
	 */
	public static function overwrite_bsn_form_setting(array $form ): ?string
	{
		return self::get_overwrite_form_setting( $form, 'form-setting-overwrite-bsn' );
	}

	/**
	 * Return the value of the overwrite KVK form setting.
	 *
	 * @since NEXT
	 */
	public static function overwrite_kvk_form_setting(array $form ): ?string
	{
		return self::get_overwrite_form_setting( $form, 'form-setting-overwrite-kvk' );
	}

	/**
	 * Return the value of an overwrite form setting.
	 *
	 * @since NEXT
	 */
	private static function get_overwrite_form_setting(array $form, string $setting_key ): ?string
	{
		$value = (string) ( $form[ sprintf( '%s-%s', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $setting_key ) ] ?? '' );
		$value = trim( $value );

		return '' !== $value ? $value : null;
	}
}
