<?php

declare(strict_types=1);

/**
 * Migrates "zaakeigenschappen" field mappings to a new version of the "zaaktype".
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.19.0
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use GFAPI;
use OWCGravityFormsZGW\ContainerResolver;

/**
 * Form fields are mapped to a "zaakeigenschap" by its URL. Every version of a "zaaktype" has
 * its own set of "eigenschappen", each with a new URL, so a new version of the "zaaktype"
 * silently breaks those mappings. This class remaps them onto the "eigenschappen" of the new
 * version, matched by their name ("naam").
 *
 * @since 1.19.0
 */
class ZaakeigenschappenMigrator
{
	protected const FIELD_PROPERTY = 'mappedFieldValueZGW';

	protected FieldSettings $field_settings;

	/**
	 * "eigenschappen" per supplier and zaaktype URL, so looping multiple forms does not refetch them.
	 */
	protected array $properties_cache = array();

	public function __construct( ?FieldSettings $field_settings = null )
	{
		$this->field_settings = $field_settings ?? new FieldSettings();
	}

	/**
	 * Hooked to "gform_pre_form_settings_save". When the zaaktype setting changes to another
	 * version of the same zaaktype (same "omschrijving"), the field mappings are migrated.
	 * The form in the database still holds the previous settings at this point.
	 */
	public function migrate_on_form_settings_save( array $form ): array
	{
		$supplier_config = FormUtils::get_supplier_config( $form );
		$supplier_name   = (string) ( $supplier_config['name'] ?? '' );

		if ( '' === $supplier_name || empty( $form['id'] ) ) {
			return $form;
		}

		$previous_form = GFAPI::get_form( (int) $form['id'] );

		if ( ! is_array( $previous_form ) || FormUtils::get_supplier_config( $previous_form ) !== $supplier_config ) {
			return $form;
		}

		$previous_identifier = FormUtils::zaaktype_identifier_form_setting( $previous_form, $supplier_name );
		$new_identifier      = FormUtils::zaaktype_identifier_form_setting( $form, $supplier_name );

		if ( '' === $previous_identifier || '' === $new_identifier || $previous_identifier === $new_identifier ) {
			return $form;
		}

		$previous_zaaktype = $this->field_settings->get_zaak_type( $supplier_name, $previous_identifier );
		$new_zaaktype      = $this->field_settings->get_zaak_type( $supplier_name, $new_identifier );

		if ( null === $previous_zaaktype || null === $new_zaaktype ) {
			return $form;
		}

		$previous_description = trim( (string) ( $previous_zaaktype->omschrijving ?? '' ) );
		$new_description      = trim( (string) ( $new_zaaktype->omschrijving ?? '' ) );

		if ( '' === $previous_description || $previous_description !== $new_description ) {
			return $form; // A different zaaktype was selected, not a new version of the same one.
		}

		return $this->migrate( $form, $supplier_name, (string) ( $previous_zaaktype->url ?? '' ), (string) ( $new_zaaktype->url ?? '' ) );
	}

	/**
	 * Remaps the "zaakeigenschappen" field mappings from the previous to the new zaaktype URL.
	 */
	public function migrate( array $form, string $supplier_name, string $previous_zaaktype_url, string $new_zaaktype_url ): array
	{
		if ( '' === $previous_zaaktype_url || '' === $new_zaaktype_url || $previous_zaaktype_url === $new_zaaktype_url ) {
			return $form;
		}

		if ( ! self::has_mapped_properties( $form ) ) {
			return $form; // Prevent API requests for forms that do not map any "zaakeigenschap".
		}

		$previous_properties = $this->get_properties( $supplier_name, $previous_zaaktype_url );
		$new_properties      = $this->get_properties( $supplier_name, $new_zaaktype_url );

		if ( array() === $previous_properties || array() === $new_properties ) {
			return $form;
		}

		return self::remap_fields( $form, $previous_properties, array_flip( $new_properties ) );
	}

	/**
	 * Replaces each mapped "zaakeigenschap" URL with the URL of the "zaakeigenschap" with the same
	 * name in the new zaaktype version. Mappings without a match are left untouched.
	 *
	 * @param array $previous_url_to_name "zaakeigenschap" URL => name, of the previous zaaktype version.
	 * @param array $new_name_to_url      "zaakeigenschap" name => URL, of the new zaaktype version.
	 */
	public static function remap_fields( array $form, array $previous_url_to_name, array $new_name_to_url ): array
	{
		foreach ( $form['fields'] ?? array() as $index => $field ) {
			$mapped_url = (string) ( $field[ self::FIELD_PROPERTY ] ?? '' );

			if ( '' === $mapped_url || in_array( $mapped_url, $new_name_to_url, true ) ) {
				continue;
			}

			$name    = $previous_url_to_name[ $mapped_url ] ?? '';
			$new_url = '' !== $name ? (string) ( $new_name_to_url[ $name ] ?? '' ) : '';

			if ( '' === $new_url ) {
				continue;
			}

			// Works for both GF_Field objects (ArrayAccess) and plain arrays.
			$form['fields'][ $index ][ self::FIELD_PROPERTY ] = $new_url;
		}

		return $form;
	}

	protected static function has_mapped_properties( array $form ): bool
	{
		foreach ( $form['fields'] ?? array() as $field ) {
			if ( str_starts_with( (string) ( $field[ self::FIELD_PROPERTY ] ?? '' ), 'https://' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the "eigenschappen" of a zaaktype as URL => name.
	 */
	protected function get_properties( string $supplier_name, string $zaaktype_url ): array
	{
		$cache_key = "{$supplier_name}|{$zaaktype_url}";

		if ( isset( $this->properties_cache[ $cache_key ] ) ) {
			return $this->properties_cache[ $cache_key ];
		}

		$properties = array();

		try {
			foreach ( $this->field_settings->get_zaak_type_properties( $supplier_name, $zaaktype_url ) as $property ) {
				$url  = trim( (string) ( $property['url'] ?? '' ) );
				$name = trim( (string) ( $property['naam'] ?? '' ) );

				if ( '' !== $url && '' !== $name ) {
					$properties[ $url ] = $name;
				}
			}
		} catch ( Exception $e ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error(
				sprintf( 'Could not retrieve the zaakeigenschappen of zaaktype "%s" to migrate the field mappings: %s', $zaaktype_url, $e->getMessage() )
			);
		}

		return $this->properties_cache[ $cache_key ] = $properties;
	}
}
