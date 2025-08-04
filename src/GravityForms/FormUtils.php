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
	public static function supplier_form_setting(array $form, bool $get_key = false ): string
	{
		$allowed  = ContainerResolver::make()->get( 'suppliers' );
		$supplier = $form[ sprintf( '%s-form-setting-supplier', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '';

		if ( ! is_array( $allowed ) || empty( $allowed ) || empty( $supplier )) {
			return '';
		}

		if ( ! in_array( $supplier, array_keys( $allowed ), true )) {
			return '';
		}

		if ($get_key) {
			return $supplier;
		}

		return $allowed[ $supplier ] ?? '';
	}

	public static function form_is_zgw( array $form ): bool
	{
		$supplier_name = self::supplier_form_setting( form: $form );
		$supplier_key  = self::supplier_form_setting( form: $form, get_key: true );

		return 0 < strlen( $supplier_name ) || 0 < strlen( $supplier_key );
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
