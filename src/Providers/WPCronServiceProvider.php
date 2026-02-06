<?php

declare(strict_types=1);

/**
 * Register WPCron service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;
use DateTimeZone;
use OWCGravityFormsZGW\WPCron\Events\PopulateFormSettings;

/**
 * Register transactions service provider.
 *
 * @since 1.1.0
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
			wp_schedule_event( $this->time_to_execute( 'tomorrow 04:00:00' ), 'daily', 'owc-gf-zgw-form_settings_cron' );
		}
	}

	/**
	 * @since 1.1.1
	 */
	protected function time_to_execute(string $datetime = 'now' ): int
	{
		$currentDateTime = new DateTime( $datetime, new DateTimeZone( wp_timezone_string() ) );

		return $currentDateTime->getTimestamp();
	}
}
