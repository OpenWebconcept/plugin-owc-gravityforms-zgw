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

use OWCGravityFormsZGW\GravityForms\FieldSettings;
use OWCGravityFormsZGW\GravityForms\FormSettings;

/**
 * Register settings service provider.
 *
 * @since 1.0.0
 */
class GravityFormsServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->registerHooks();
	}

	private function registerHooks(): void
	{
		add_filter( 'gform_form_settings_fields', ( new FormSettings() )->add_form_settings( ... ), 10, 2 );
		add_action( 'gform_field_standard_settings', ( new FieldSettings() )->add_select( ... ), 10, 2 );
		add_action( 'gform_editor_js', ( new FieldSettings() )->add_select_script( ... ), 10, 0 );
	}
}
