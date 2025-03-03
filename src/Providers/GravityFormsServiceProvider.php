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
		add_filter( 'gform_form_settings_fields', ( new FormSettings() )->addFormSettings( ... ), 10, 2 );
	}
}
