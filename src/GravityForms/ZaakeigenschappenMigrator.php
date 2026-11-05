<?php

declare(strict_types=1);

/**
 * Migrates "zaakeigenschappen" field mappings to the current version of the "zaaktype".
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
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
use GFCommon;
use OWCGravityFormsZGW\ContainerResolver;
use Throwable;

use function OWC\ZGW\apiClient;

/**
 * Form fields are mapped to a "zaakeigenschap" by its URL. Every version of a "zaaktype" has
 * its own set of "eigenschappen", each with a new URL, so a new version of the "zaaktype"
 * silently breaks those mappings. Depending on the supplier the "zaaktype" URL itself changes
 * (e.g. OpenZaak) or stays the same (e.g. Decos JOIN), so this class does not rely on that URL.
 *
 * Instead it validates every mapping against the "eigenschappen" of the current "zaaktype" and
 * remaps the ones that are no longer part of it onto the "eigenschap" with the same name ("naam").
 *
 * @since NEXT
 */
class ZaakeigenschappenMigrator
{
	public const FIELD_PROPERTY      = 'mappedFieldValueZGW';
	public const FIELD_NAME_PROPERTY = 'mappedFieldNameZGW';

	protected FieldSettings $field_settings;

	/**
	 * "eigenschappen" per supplier and zaaktype URL, so looping multiple forms does not refetch them.
	 */
	protected array $properties_cache = array();

	/**
	 * Names of single "eigenschappen" per supplier and URL, fetched when a mapping has no stored name.
	 */
	protected array $property_name_cache = array();

	public function __construct( ?FieldSettings $field_settings = null )
	{
		$this->field_settings = $field_settings ?? new FieldSettings();
	}

	/**
	 * Hooked to "gform_pre_form_settings_save". Validates the field mappings against the selected
	 * zaaktype, as long as it is (a version of) the previously selected zaaktype.
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

		if ( '' === $previous_identifier || '' === $new_identifier ) {
			return $form;
		}

		$new_zaaktype = $this->field_settings->get_zaak_type( $supplier_name, $new_identifier );

		if ( null === $new_zaaktype ) {
			return $form;
		}

		if ( $previous_identifier === $new_identifier ) {
			return $this->migrate( $form, $supplier_name, (string) ( $new_zaaktype->url ?? '' ) );
		}

		$previous_zaaktype = $this->field_settings->get_zaak_type( $supplier_name, $previous_identifier );

		if ( null === $previous_zaaktype ) {
			return $form;
		}

		$previous_description = trim( (string) ( $previous_zaaktype->omschrijving ?? '' ) );
		$new_description      = trim( (string) ( $new_zaaktype->omschrijving ?? '' ) );

		if ( '' === $previous_description || $previous_description !== $new_description ) {
			return $form; // A different zaaktype was selected, not a new version of the same one.
		}

		return $this->migrate( $form, $supplier_name, (string) ( $new_zaaktype->url ?? '' ), (string) ( $previous_zaaktype->url ?? '' ) );
	}

	/**
	 * Hooked to "gform_admin_pre_render". Validates the field mappings when the form editor opens,
	 * so nobody sees an empty mapping after a new version of the zaaktype has been published.
	 *
	 * @param mixed $form
	 *
	 * @return mixed
	 */
	public function migrate_on_form_editor( $form )
	{
		if ( ! is_array( $form ) || ! class_exists( GFCommon::class ) || ! GFCommon::is_form_editor() ) {
			return $form;
		}

		return $this->migrate_and_persist( $form );
	}

	/**
	 * Validates the field mappings against the zaaktype saved in the form settings and stores the
	 * remapped ones, so the form does not have to wait for the next zaaktypen fetch (cron or cache miss).
	 * Returns the migrated form, or the given form when nothing changed. Never throws, so callers
	 * such as the creation of a zaak can always proceed.
	 */
	public function migrate_and_persist( array $form ): array
	{
		try {
			$migrated = $this->migrate_against_saved_zaaktype( $form );

			if ( self::mapping_signature( $migrated ) === self::mapping_signature( $form ) ) {
				return $form;
			}

			$this->persist( (int) ( $form['id'] ?? 0 ) );

			return $migrated;
		} catch ( Throwable $e ) {
			$this->log_error( sprintf( 'Could not migrate the zaakeigenschappen field mappings of form "%s": %s', (string) ( $form['id'] ?? '' ), $e->getMessage() ) );

			return $form;
		}
	}

	/**
	 * Stores the migrated mappings. The form is reloaded from the database instead of saving the given one,
	 * which may have been altered at runtime (e.g. by "gform_pre_render" filters during a submission).
	 */
	protected function persist( int $form_id ): void
	{
		$stored_form = 0 < $form_id ? GFAPI::get_form( $form_id ) : false;

		if ( ! is_array( $stored_form ) ) {
			return;
		}

		$migrated = $this->migrate_against_saved_zaaktype( $stored_form );

		if ( self::mapping_signature( $migrated ) === self::mapping_signature( $stored_form ) ) {
			return;
		}

		$result = GFAPI::update_form( $migrated );

		if ( is_wp_error( $result ) ) {
			$this->log_error( sprintf( 'Could not save the migrated zaakeigenschappen field mappings of form "%d": %s', $form_id, $result->get_error_message() ) );
		}
	}

	protected function migrate_against_saved_zaaktype( array $form ): array
	{
		$supplier_name = (string) ( FormUtils::get_supplier_config( $form )['name'] ?? '' );

		if ( '' === $supplier_name || ! self::has_mapped_properties( $form ) ) {
			return $form;
		}

		$identifier = FormUtils::zaaktype_identifier_form_setting( $form, $supplier_name );
		$zaaktype   = '' !== $identifier ? $this->field_settings->get_zaak_type( $supplier_name, $identifier ) : null;

		return null === $zaaktype ? $form : $this->migrate( $form, $supplier_name, (string) ( $zaaktype->url ?? '' ) );
	}

	/**
	 * Validates the "zaakeigenschappen" field mappings against the zaaktype and remaps the outdated ones.
	 *
	 * @param string $zaaktype_url          URL of the current zaaktype version.
	 * @param string $previous_zaaktype_url URL of the previous zaaktype version, when the supplier changes it per version.
	 */
	public function migrate( array $form, string $supplier_name, string $zaaktype_url, string $previous_zaaktype_url = '' ): array
	{
		if ( ! filter_var( $zaaktype_url, FILTER_VALIDATE_URL ) || ! self::has_mapped_properties( $form ) ) {
			return $form; // Prevent API requests for forms that do not map any "zaakeigenschap".
		}

		$current_properties = $this->get_properties( $supplier_name, $zaaktype_url );

		if ( array() === $current_properties ) {
			return $form;
		}

		$previous_properties = '' !== $previous_zaaktype_url && $previous_zaaktype_url !== $zaaktype_url
			? $this->get_properties( $supplier_name, $previous_zaaktype_url )
			: array();

		return self::remap_fields(
			$form,
			$current_properties,
			fn ( string $url ): string => $previous_properties[ $url ] ?? $this->get_property_name( $supplier_name, $url )
		);
	}

	/**
	 * Replaces each mapped "zaakeigenschap" URL that is not part of the current zaaktype with the URL
	 * of the "zaakeigenschap" with the same name. The name is taken from the mapping itself when stored,
	 * otherwise resolved by the given callback. Mappings without a match are left untouched.
	 * Valid mappings get their name stored, so a next version can be migrated without extra requests.
	 *
	 * @param array    $current_url_to_name "zaakeigenschap" URL => name, of the current zaaktype version.
	 * @param callable $resolve_name        Receives an outdated "zaakeigenschap" URL, returns its name or ''.
	 */
	public static function remap_fields( array $form, array $current_url_to_name, callable $resolve_name ): array
	{
		$current_name_to_url = array_flip( $current_url_to_name );

		foreach ( $form['fields'] ?? array() as $index => $field ) {
			$mapped_url = (string) ( $field[ self::FIELD_PROPERTY ] ?? '' );

			if ( ! self::is_property_url( $mapped_url ) ) {
				continue;
			}

			// Valid when the mapped URL belongs to the current zaaktype version. Otherwise, remap it onto the
			// "eigenschap" with the same name, taken from the field or resolved by the callback.
			if ( isset( $current_url_to_name[ $mapped_url ] ) ) {
				$name = $current_url_to_name[ $mapped_url ];
			} else {
				$name = trim( (string) ( $field[ self::FIELD_NAME_PROPERTY ] ?? '' ) );
				$name = '' !== $name ? $name : trim( (string) $resolve_name( $mapped_url ) );

				if ( '' === $name || ! isset( $current_name_to_url[ $name ] ) ) {
					continue;
				}

				// Works for both GF_Field objects (ArrayAccess) and plain arrays.
				$form['fields'][ $index ][ self::FIELD_PROPERTY ] = (string) $current_name_to_url[ $name ];
			}

			if ( ( $field[ self::FIELD_NAME_PROPERTY ] ?? '' ) !== $name ) {
				$form['fields'][ $index ][ self::FIELD_NAME_PROPERTY ] = $name;
			}
		}

		return $form;
	}

	/**
	 * Returns the "zaakeigenschappen" mappings of a form, used to detect whether a migration changed anything.
	 */
	public static function mapping_signature( array $form ): array
	{
		$signature = array();

		foreach ( $form['fields'] ?? array() as $field ) {
			$signature[] = array( (string) ( $field[ self::FIELD_PROPERTY ] ?? '' ), (string) ( $field[ self::FIELD_NAME_PROPERTY ] ?? '' ) );
		}

		return $signature;
	}

	protected static function is_property_url( string $value ): bool
	{
		return str_starts_with( $value, 'https://' ); // Same check as used when creating the zaak.
	}

	protected static function has_mapped_properties( array $form ): bool
	{
		foreach ( $form['fields'] ?? array() as $field ) {
			if ( self::is_property_url( (string) ( $field[ self::FIELD_PROPERTY ] ?? '' ) ) ) {
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
			$this->log_error( sprintf( 'Could not retrieve the zaakeigenschappen of zaaktype "%s" to migrate the field mappings: %s', $zaaktype_url, $e->getMessage() ) );
		}

		return $this->properties_cache[ $cache_key ] = $properties;
	}

	/**
	 * Fetches the name of a single (outdated) "eigenschap" by its URL. Used for mappings saved before
	 * the name was stored alongside the URL, when the previous zaaktype version cannot be listed.
	 */
	protected function get_property_name( string $supplier_name, string $property_url ): string
	{
		$cache_key = "{$supplier_name}|{$property_url}";

		if ( isset( $this->property_name_cache[ $cache_key ] ) ) {
			return $this->property_name_cache[ $cache_key ];
		}

		$name = '';

		try {
			// The endpoint does not add a slash between the endpoint and identifier (owc/zgw-api 2.6.2).
			$property = apiClient( $supplier_name )->eigenschappen()->get( '/' . basename( (string) wp_parse_url( $property_url, PHP_URL_PATH ) ) );
			$name     = null === $property ? '' : trim( (string) ( $property['naam'] ?? '' ) );
		} catch ( Exception $e ) {
			$this->log_error( sprintf( 'Could not retrieve zaakeigenschap "%s" to migrate the field mapping: %s', $property_url, $e->getMessage() ) );
		}

		return $this->property_name_cache[ $cache_key ] = $name;
	}

	protected function log_error( string $message ): void
	{
		ContainerResolver::make()->get( 'logger.zgw' )->error( $message );
	}
}
