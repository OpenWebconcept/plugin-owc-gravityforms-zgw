<?php

declare(strict_types=1);

/**
 * Register settings service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFAddOn;
use GFForms;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\GravityForms\FieldGroups;
use OWCGravityFormsZGW\GravityForms\FieldSettings;
use OWCGravityFormsZGW\GravityForms\FormSettings;
use OWCGravityFormsZGW\GravityForms\ZGWAddon;
use OWCGravityFormsZGW\Transactions\Controllers\TransactionController;
use OWCGravityFormsZGW\Verzoeken\VerzoekController;

/**
 * Register settings service provider.
 *
 * @since 1.0.0
 */
class GravityFormsServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->register_hooks();
		$this->register_addon();
	}

	/**
	 * @since 1.0.0
	 */
	private function register_hooks(): void
	{
		// Form settings.
		add_filter( 'gform_form_settings_fields', ( new FormSettings() )->add_form_settings( ... ), 10, 2 );

		// Field tab settings.
		add_action( 'gform_editor_js', ( new FieldSettings() )->add_select_script( ... ), 10, 0 );
		add_filter( 'gform_field_groups_form_editor', ( new FieldGroups() )->field_groups_form_editor( ... ), 10, 1 );
		add_filter( 'gform_field_settings_tabs', ( new FieldGroups() )->add_tabs( ... ), 10, 1 );
		add_action( 'gform_field_settings_tab_content_owc_gf_zgw', ( new FieldGroups() )->add_tab_content( ... ), 10, 1 );

		// After submission hooks.
		add_action( 'gform_after_submission', ( new TransactionController() )->create( ... ), 10, 2 );
		add_action( 'gform_after_submission', ( new ZaakController() )->handle( ... ), 20, 2 );
		add_action( 'gform_after_submission', ( new VerzoekController() )->handle( ... ), 20, 2 );
	}

	/**
	 * @since 1.0.0
	 */
	public function register_addon(): void
	{
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		GFForms::include_addon_framework();
		GFAddOn::register( ZGWAddon::class );
		ZGWAddon::get_instance();
	}
}
