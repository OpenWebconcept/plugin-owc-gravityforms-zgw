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
if ( ! defined( 'ABSPATH' )) {
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

		foreach ($clients as $clientConfig) {
			if (strtolower( $clientConfig['name'] ) === $supplier) {
				return $clientConfig;
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
}
