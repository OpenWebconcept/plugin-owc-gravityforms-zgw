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
use OWCGravityFormsZGW\GravityForms\ZaakeigenschappenMigrator;

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
	 * @since 1.16.0
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
	 * @since 1.16.0
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
	 * Only runs when zaaktypen are freshly fetched (cache miss), so all versions and the latest versions are both in memory.
	 *
	 * @since 1.10.0
	 * @since NEXT Also validates the "zaakeigenschappen" field mappings against the latest zaaktype version.
	 */
	protected function replace_old_zaaktypen_after_fetch_in_form_settings( array $all_zaaktype_versions, array $latest_zaaktype_versions ): void
	{
		$this->cache_producten_of_diensten( $latest_zaaktype_versions );

		$saved_zaaktype_url_to_description  = $this->map_all_zaaktype_urls_to_descriptions( $all_zaaktype_versions );
		$description_to_latest_zaaktype_url = $this->map_descriptions_to_latest_zaaktype_urls( $latest_zaaktype_versions );

		$prefix         = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;
		$supplier_key   = strtolower( $this->supplier_name );
		$setting_key    = "{$prefix}-form-setting-{$supplier_key}-identifier";
		$manual_key     = "{$prefix}-form-setting-supplier-manually";
		$supplier_field = "{$prefix}-form-setting-supplier";
		$migrator       = new ZaakeigenschappenMigrator();

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

			// Without a description or a newer URL, the saved URL is kept: it may still be the latest version.
			$omschrijving = $saved_zaaktype_url_to_description[ $saved_url ] ?? '';
			$winning_url  = '' !== $omschrijving ? ( $description_to_latest_zaaktype_url[ $omschrijving ] ?? '' ) : '';
			$current_url  = '' !== $winning_url ? $winning_url : $saved_url;

			/**
			 * Some suppliers (e.g. Decos JOIN) keep the zaaktype URL the same across versions while the
			 * "eigenschappen" do get new URLs, so the mappings are validated on every fetch.
			 */
			$mapping_signature = ZaakeigenschappenMigrator::mapping_signature( $form );
			$form              = $migrator->migrate( $form, $this->supplier_name, $current_url, $saved_url );

			if ( $current_url === $saved_url && ZaakeigenschappenMigrator::mapping_signature( $form ) === $mapping_signature ) {
				continue; // Nothing changed, prevent a needless update.
			}

			$form[ $setting_key ] = $current_url;
			GFAPI::update_form( $form );
		}
	}

	/**
	 * Prepare unfiltered zaaktypen by filtering out types with empty 'omschrijving' (description) or 'url'
	 * and return a mapped array with 'url' as key and 'omschrijving' (description) as value.
	 *
	 * @since 1.10.0
	 */
	protected function map_all_zaaktype_urls_to_descriptions( array $zaaktypen ): array
	{
		$url_to_description = array();

		foreach ( $zaaktypen as $zaaktype ) {
			$omschrijving = trim( (string) ( $zaaktype->omschrijving ?? '' ) );
			$url          = trim( (string) ( $zaaktype->url ?? '' ) );

			if ( '' !== $omschrijving && '' !== $url ) {
				$url_to_description[ $url ] = $omschrijving;
			}
		}

		return $url_to_description;
	}

	/**
	 * Prepare filtered zaaktypen by filtering out types with empty 'omschrijving' (description) or 'url'
	 * and return a mapped array with 'omschrijving' (description) as key and 'url' as value.
	 *
	 * @since 1.10.0
	 */
	protected function map_descriptions_to_latest_zaaktype_urls( array $zaaktypen ): array
	{
		$description_to_url = array();

		foreach ( $zaaktypen as $zaaktype ) {
			$url          = trim( (string) ( $zaaktype->url ?? '' ) );
			$omschrijving = trim( (string) ( $zaaktype->omschrijving ?? '' ) );

			if ( '' !== $url && '' !== $omschrijving ) {
				$description_to_url[ $omschrijving ] = $url;
			}
		}

		return $description_to_url;
	}

	/**
	 * Caches the "productenOfDiensten" per zaaktype, keyed by normalized identifier. The zaaktypen
	 * list response already contains this field (unlike e.g. "statustypen", it isn't a separate
	 * lazily-fetched resource), so this is built from data already in memory — no extra API calls.
	 *
	 * @since 1.16.0
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
