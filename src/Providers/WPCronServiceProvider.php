<?php
/**
 * Register WPCron service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\WPCron\Events\PopulateFormSettings;

/**
 * Register transactions service provider.
 *
 * @since NEXT
 */
class WPCronServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->register_hooks();
		$this->register_events();
	}

	protected function register_hooks(): void {
		add_action( 'owc-gf-zgw-form_settings_cron', ( new PopulateFormSettings() )->init( ... ) );
	}

	protected function register_events(): void
	{
		if ( ! wp_next_scheduled( 'owc-gf-zgw-form_settings_cron' ) ) {
			wp_schedule_event( $this->timeToExecute( 'tomorrow 04:00:00' ), 'daily', 'owc-gf-zgw-form_settings_cron' );
		}
	}
}
