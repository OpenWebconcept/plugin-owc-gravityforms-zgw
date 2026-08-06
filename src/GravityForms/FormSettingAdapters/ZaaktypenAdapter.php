<?php

declare(strict_types=1);

/**
 * Adapter for zaaktypen.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms\FormSettingAdapters;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use GFAPI;
use OWC\ZGW\Entities\Zaaktype;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Adapter for zaaktypen.
 *
 * @since 1.0.0
 */
class ZaaktypenAdapter extends Adapter
{
	/**
	 * Builds the transient key used to cache the zaaktypen of a supplier.
	 *
	 * @since 1.14.0
	 */
	public static function transient_key( string $supplier_name ): string
	{
		return sprintf( '%s-form-settings-zaaktypen', self::key_prefix( $supplier_name ) );
	}

	/**
	 * Builds the transient key used to cache the "productenOfDiensten" per zaaktype of a supplier.
	 *
	 * @since NEXT
	 */
	public static function products_transient_key( string $supplier_name ): string
	{
		return sprintf( '%s-form-settings-zaaktypen-producten', self::key_prefix( $supplier_name ) );
	}

	/**
	 * Returns the "productenOfDiensten" cached for the given zaaktype (URL or bare identifier).
	 * The cache is populated as a side effect of handle() and refreshed daily by the
	 * "populate form settings" cron, so this never triggers a live API request.
	 *
	 * @since NEXT
	 */
	public static function get_cached_products( string $supplier_name, string $zaaktype_identifier ): array
	{
		$map = get_transient( self::products_transient_key( $supplier_name ) );

		if ( ! is_array( $map ) ) {
			return array();
		}

		$key = FormUtils::normalize_zaaktype_identifier( $zaaktype_identifier );

		return is_array( $map[ $key ] ?? null ) ? $map[ $key ] : array();
	}

	/**
	 * Loops all Gravity Forms and updates any saved zaaktype URL that has been replaced by a newer version.
	 * Only runs when zaaktypen are freshly fetched (cache miss), so the raw and filtered data are both in memory.
	 *
	 * @since 1.10.0
	 */
	protected function replace_old_zaaktypen_after_fetch_in_form_settings( array $raw, array $filtered ): void
	{
		$this->cache_producten_of_diensten( $filtered );

		$saved_zaaktype_url_to_description  = $this->map_all_zaaktype_urls_to_descriptions( $raw );
		$description_to_latest_zaaktype_url = $this->map_descriptions_to_latest_zaaktype_urls( $filtered );

		$prefix         = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;
		$supplier_key   = strtolower( $this->supplier_name );
		$setting_key    = "{$prefix}-form-setting-{$supplier_key}-identifier";
		$manual_key     = "{$prefix}-form-setting-supplier-manually";
		$supplier_field = "{$prefix}-form-setting-supplier";

		foreach ( GFAPI::get_forms() as $form ) {
			if ( '1' === ( $form[ $manual_key ] ?? '0' ) ) {
				continue; // Skip forms that are marked as manually configured, as we don't want to override manual settings.
			}

			if ( ( $form[ $supplier_field ] ?? '' ) !== $supplier_key ) {
				continue; // Skip forms that are not associated with the current supplier, as we only want to update forms related to this supplier.
			}

			$saved_url = (string) ( $form[ $setting_key ] ?? '' );
			if ( '' === $saved_url ) {
				continue; // Skip forms that don't have a saved URL, as there's nothing to update.
			}

			$omschrijving = $saved_zaaktype_url_to_description[ $saved_url ] ?? '';
			if ( '' === $omschrijving ) {
				continue; // Skip if we can't find a description for the saved URL, as we won't be able to find the new URL without the description.
			}

			$winning_url = $description_to_latest_zaaktype_url[ $omschrijving ] ?? '';
			if ( '' === $winning_url || $winning_url === $saved_url ) {
				continue; // Skip if we can't find a new URL for the description or if the new URL is the same as the saved URL, as there's no update needed.
			}

			$form[ $setting_key ] = $winning_url;
			GFAPI::update_form( $form );
		}
	}

	/**
	 * Prepare unfiltered zaaktypen by filtering out types with empty 'omschrijving' (description) or 'url'
	 * and return a mapped array with 'url' as key and 'omschrijving' (description) as value.
	 *
	 * @since 1.10.0
	 */
	protected function map_all_zaaktype_urls_to_descriptions( array $raw ): array
	{
		$prepared_description_to_latest_zaaktype_url = array();

		foreach ( $raw as $zaaktype ) {
			$omschrijving = trim( (string) ( $zaaktype->omschrijving ?? '' ) );
			$url          = trim( (string) ( $zaaktype->url ?? '' ) );

			if ( '' !== $omschrijving && '' !== $url ) {
				$prepared_description_to_latest_zaaktype_url[ $url ] = $omschrijving;
			}
		}

		return $prepared_description_to_latest_zaaktype_url;
	}

	/**
	 * Prepare filtered zaaktypen by filtering out types with empty 'omschrijving' (description) or 'url'
	 * and return a mapped array with 'omschrijving' (description) as key and 'url' as value.
	 *
	 * @since 1.10.0
	 */
	protected function map_descriptions_to_latest_zaaktype_urls( array $raw ): array
	{
		$saved_zaaktype_url_to_description = array();

		foreach ( $raw as $zaaktype ) {
			$url          = trim( (string) ( $zaaktype->url ?? '' ) );
			$omschrijving = trim( (string) ( $zaaktype->omschrijving ?? '' ) );

			if ( '' !== $url && '' !== $omschrijving ) {
				$saved_zaaktype_url_to_description[ $omschrijving ] = $url;
			}
		}

		return $saved_zaaktype_url_to_description;
	}

	/**
	 * Caches the "productenOfDiensten" per zaaktype, keyed by normalized identifier. The zaaktypen
	 * list response already contains this field (unlike e.g. "statustypen", it isn't a separate
	 * lazily-fetched resource), so this is built from data already in memory — no extra API calls.
	 *
	 * @since NEXT
	 */
	private function cache_producten_of_diensten( array $zaaktypen ): void
	{
		$products_by_identifier = array();

		foreach ( $zaaktypen as $zaaktype ) {
			$url = trim( (string) ( $zaaktype->url ?? '' ) );

			if ( '' === $url || ! is_array( $zaaktype->productenOfDiensten ) ) {
				continue;
			}

			$key = FormUtils::normalize_zaaktype_identifier( $url );

			$products_by_identifier[ $key ] = array_values( array_filter( $zaaktype->productenOfDiensten, 'is_string' ) );
		}

		set_transient( self::products_transient_key( $this->supplier_name ), $products_by_identifier, self::TRANSIENT_LIFETIME_IN_SECONDS );
	}

	/**
	 * @since 1.0.0
	 */
	public function handle(): array
	{
		try {
			return $this->get_types(
				self::transient_key( $this->supplier_name ),
				'zaaktypen',
				function ( Zaaktype $zaaktype ) {
					$identification = (string) ( $zaaktype->identificatie ?? '' );
					$description    = (string) ( $zaaktype->omschrijving ?? '' );
					$url            = (string) ( $zaaktype->url ?? '' );

					if ( empty( $identification ) || empty( $description ) || empty( $url ) ) {
						return array();
					}

					return array(
						'name'  => $identification,
						'label' => "{$description} ({$identification})",
						'value' => $url, // @todo: when the api supports filtering on zaaktype identification this line should be updated to $identification.
					);
				},
				'No zaaktypen found.'
			);
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );

			return $this->handle_no_choices( 'zaaktypen' );
		}
	}
}
