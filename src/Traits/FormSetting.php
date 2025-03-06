<?php

declare(strict_types=1);

namespace OWCGravityFormsZGW\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCGravityFormsZGW\ContainerResolver;

trait FormSetting
{
	/**
	 * Checks if the form setting is selected or configured manually.
	 * Returns the selected zaaktype identifier.
	 *
	 * @since 1.0.0
	 */
	public function zaaktype_identifier_form_setting(array $form, string $supplier_key ): string
	{
		if ('1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' )) {
			$zaaktypeIdentifier = $form[ sprintf( '%s-form-setting-%s-identifier-manual', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_key ) ] ?? null;
		} else {
			$zaaktypeIdentifier = $form[ sprintf( '%s-form-setting-%s-identifier', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_key ) ] ?? null;
		}

		return ! empty( $zaaktypeIdentifier ) ? $zaaktypeIdentifier : '';
	}

	/**
	 * Checks if the form setting is selected or configured manually.
	 * Returns the selected information object type identifier.
	 *
	 * @since 1.0.0
	 */
	public function information_object_type_form_setting(array $form, string $supplier_key ): string
	{
		if ('1' === ( $form[ sprintf( '%s-form-setting-supplier-manually', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '0' )) {
			$zaaktypeIdentifier = $form[ sprintf( '%s-form-setting-%s-information-object-type-manual', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_key ) ] ?? null;
		} else {
			$zaaktypeIdentifier = $form[ sprintf( '%s-form-setting-%s-information-object-type', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX, $supplier_key ) ] ?? null;
		}

		return ! empty( $zaaktypeIdentifier ) ? $zaaktypeIdentifier : '';
	}

	/**
	 * Get the supplier configured in the form settings.
	 *
	 * @since 1.0.0
	 */
	public function supplier_form_setting(array $form, bool $get_key = false ): string
	{
		$allowed  = ContainerResolver::make()->get( 'suppliers' );
		$supplier = $form[ sprintf( '%s-form-setting-supplier', OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX ) ] ?? '';

		if ( ! is_array( $allowed ) || empty( $allowed ) || empty( $supplier )) {
			return '';
		}

		if ( ! in_array( $supplier, array_keys( $allowed ) )) {
			return '';
		}

		if ($get_key) {
			return $supplier;
		}

		return $allowed[ $supplier ] ?? '';
	}
}
