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
use OWCGravityFormsZGW\GravityForms\FieldGroups;
use OWCGravityFormsZGW\GravityForms\FieldSettings;
use OWCGravityFormsZGW\GravityForms\FormSettings;
use OWCGravityFormsZGW\GravityForms\ZGWAddon;
use OWCGravityFormsZGW\Controllers\ActionSchedulerController;
use OWCGravityFormsZGW\Transactions\Controllers\TransactionController;
use OWCGravityFormsZGW\GravityForms\Controllers\PaymentController;
use OWCGravityFormsZGW\GravityForms\Controllers\NotificationController;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakMergeTagsController;

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
		add_action( 'admin_init', ( new FormSettings() )->sync_zaaktypen_on_settings_page( ... ), 9 );
		add_filter( 'gform_form_settings_fields', ( new FormSettings() )->add_form_settings( ... ), 10, 2 );

		// Field tab settings.
		add_action( 'gform_editor_js', ( new FieldSettings() )->add_select_script( ... ), 10, 0 );
		add_filter( 'gform_field_groups_form_editor', ( new FieldGroups() )->field_groups_form_editor( ... ), 10, 1 );
		add_filter( 'gform_field_settings_tabs', ( new FieldGroups() )->add_tabs( ... ), 10, 1 );
		add_action( 'gform_field_settings_tab_content_owc_gf_zgw', ( new FieldGroups() )->add_tab_content( ... ), 10, 1 );

		// Notifications and merge tags.
		add_filter( 'gform_disable_notifications', ( new NotificationController() )->disable_notifications( ... ), 10, 4 );
		add_filter( 'gform_replace_merge_tags', ( new ZaakMergeTagsController() )->replace_zaak_merge_tags( ... ), 10, 7 );
		add_filter( 'gform_custom_merge_tags', ( new ZaakMergeTagsController() )->add_zaak_merge_tags( ... ), 10, 2 );

		// After submission hooks.
		add_action( 'gform_after_submission', ( new ActionSchedulerController() )->schedule_single_actions( ... ), 10, 2 );
		add_action( 'gform_after_submission', ( new TransactionController() )->create( ... ), 10, 2 );

		// Post payment hooks.
		add_action( 'gform_post_payment_action', ( new PaymentController() )->post_payment_failed( ... ), 10, 2 );
		add_action( 'gform_post_payment_completed', ( new PaymentController() )->post_payment_completed( ... ), 10, 2 );

		// Action scheduler hooks.
		add_action( OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_ZAAK, ( new ActionSchedulerController() )->handle_zaak_creation( ... ), 30, 2 );
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
