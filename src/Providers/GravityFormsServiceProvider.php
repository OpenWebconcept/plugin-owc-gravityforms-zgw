<?php
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
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use GFAddOn;
use GFForms;
use OWCGravityFormsZGW\GravityForms\Controllers\FormConfirmationController;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakControllerSubmissionPDF;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakUploadsController;
use OWCGravityFormsZGW\GravityForms\FieldSettings;
use OWCGravityFormsZGW\GravityForms\FormSettings;
use OWCGravityFormsZGW\GravityForms\ZGWAddon;

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
		add_filter( 'gform_form_settings_fields', ( new FormSettings() )->add_form_settings( ... ), 10, 2 );
		add_action( 'gform_field_standard_settings', ( new FieldSettings() )->add_select( ... ), 10, 2 );
		add_action( 'gform_editor_js', ( new FieldSettings() )->add_select_script( ... ), 10, 0 );
		add_action( 'gform_validation', ( new ZaakController() )->handle( ... ), 10, 1 );
		add_action( 'gform_pre_submission_filter', ( new ZaakUploadsController() )->handle( ... ), 10, 1 );
		add_action( 'gform_after_submission', ( new ZaakControllerSubmissionPDF() )->handle( ... ), 999, 2 );
		add_filter( 'gform_confirmation', ( new FormConfirmationController() )->handle( ... ), 10, 3 );
	}

	/**
	 * @since 1.0.0
	 */
	public function register_addon(): void
	{
		if ( ! method_exists( 'GFForms', 'include_addon_framework' )) {
			return;
		}

		GFForms::include_addon_framework();
		GFAddOn::register( ZGWAddon::class );
		ZGWAddon::get_instance();
	}
}
