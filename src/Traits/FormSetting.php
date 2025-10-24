<?php
/**
 * Form setting trait.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Form setting trait.
 *
 * @since 1.0.0
 */
trait FormSetting
{
	/**
	 * Checks if the form setting is selected or configured manually.
	 * Returns the selected zaaktype identifier.
	 */
	public function zaaktype_identifier_form_setting(array $form, string $supplier_name ): string
	{
		$supplier_name = strtolower( $supplier_name );

		if ('1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' )) {
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
	public function information_object_type_form_setting(array $form, string $supplier_name ): string
	{
		if ('1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' )) {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-information-object-type-manual', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		} else {
			$zaaktype_identifier = $form[ sprintf( '%s-form-setting-%s-information-object-type', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_name ) ] ?? null;
		}

		return ! empty( $zaaktype_identifier ) ? $zaaktype_identifier : '';
	}
}
