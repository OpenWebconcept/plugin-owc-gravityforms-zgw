<?php

declare(strict_types=1);

/**
 * Controller for manually clearing the cached zaaktypen and informatieobjecttypen.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;

/**
 * Controller for manually clearing the cached zaaktypen and informatieobjecttypen.
 *
 * @since NEXT
 */
class FormSettingsCacheController
{
	public const ACTION      = 'owc-gf-zgw-clear-form-settings-cache';
	public const NONCE_QUERY = 'owc-gf-zgw-clear-form-settings-cache-nonce';
	public const NOTICE_FLAG = 'owc-gf-zgw-form-settings-cache-cleared';

	/**
	 * Returns the capability required to see and use the "clear cache" action.
	 *
	 * Defaults to 'manage_options', in which case only administrators can see or use it.
	 * Filtering this to a different capability additionally exposes the settings page
	 * (and only the cache section on it) to any user with that capability.
	 */
	public static function allowed_capability(): string
	{
		return (string) apply_filters( 'owc_gf_zgw_form_settings_cache_capability', 'manage_options' );
	}

	/**
	 * Whether the current user is allowed to see and use the "clear cache" action.
	 */
	public function current_user_can_manage(): bool
	{
		return current_user_can( 'manage_options' ) || current_user_can( self::allowed_capability() );
	}

	/**
	 * Renders the button used to manually clear the cached zaaktypen and informatieobjecttypen.
	 */
	public function render_clear_cache_button(): void
	{
		$url = wp_nonce_url( admin_url( sprintf( 'admin-post.php?action=%s', self::ACTION ) ), self::ACTION, self::NONCE_QUERY );

		printf(
			'<p><a href="%s" class="button">%s</a></p>',
			esc_url( $url ),
			esc_html__( 'Zaaktypen en informatieobjecttypen opnieuw ophalen', 'owc-gravityforms-zgw' )
		);
	}

	/**
	 * Handles the request to manually clear the cached zaaktypen and informatieobjecttypen of all configured suppliers.
	 */
	public function handle_clear_cache_request(): void
	{
		if ( ! $this->current_user_can_manage() ) {
			wp_die( esc_html__( 'Je hebt geen toestemming om deze actie uit te voeren.', 'owc-gravityforms-zgw' ) );
		}

		check_admin_referer( self::ACTION, self::NONCE_QUERY );

		foreach ( $this->get_configured_suppliers() as $supplier ) {
			delete_transient( ZaaktypenAdapter::transient_key( $supplier['name'] ) );
			delete_transient( InformatieobjecttypeAdapter::transient_key( $supplier['name'] ) );
		}

		wp_safe_redirect( add_query_arg( self::NOTICE_FLAG, '1', wp_get_referer() ?: admin_url() ) );
		exit;
	}

	/**
	 * Shows an admin notice right after the cache has been cleared.
	 */
	public function render_cache_cleared_notice(): void
	{
		if ( ! isset( $_GET[ self::NOTICE_FLAG ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'De cache van de zaaktypen en informatieobjecttypen is geleegd. Ze worden opnieuw opgehaald bij het volgende gebruik of bij de volgende geplande taak.', 'owc-gravityforms-zgw' )
		);
	}

	private function get_configured_suppliers(): array
	{
		$suppliers = ContainerResolver::make()->get( 'zgw.api-configured-clients' );

		return is_array( $suppliers ) ? array_filter( $suppliers, fn( $supplier ) => isset( $supplier['name'] ) ) : array();
	}
}
