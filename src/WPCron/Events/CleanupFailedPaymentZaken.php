<?php

declare(strict_types=1);

/**
 * Event used to clean up created Zaken after a failed payment.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\WPCron\Events;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;
use DateTimeZone;
use GFAPI;
use OWCGravityFormsZGW\GravityForms\Controllers\PaymentController;

/**
 * Event used to clean up created Zaken after a failed payment.
 *
 * @since NEXT
 */
class CleanupFailedPaymentZaken
{
	public function init(): void
	{
		$entries = $this->get_pending_payment_entries();

		if ( 0 === count( $entries ) ) {
			return;
		}

		$this->process_pending_payment_entries( $entries );
	}

	/**
	 * Retrieves entries that have a pending payment status and an associated zaak UUID.
	 */
	private function get_pending_payment_entries(): array
	{
		$args = array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'      => 'owc_gz_created_zaak_uuid',
					'value'    => '',
					'operator' => '!=',
				),
				array(
					'key'   => 'payment_status',
					'value' => 'Pending',
				),
			),
		);

		$entries = GFAPI::get_entries( 0, $args );

		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Processes each entry with a pending payment status.
	 */
	private function process_pending_payment_entries(array $entries ): void
	{
		foreach ( $entries as $entry ) {
			$this->evaluate_pending_payment_entry( $entry );
		}
	}

	/**
	 * Evaluates a single entry to determine if the payment has been pending for too long.
	 * If so, triggers the appropriate action (e.g., delete the zaak).
	 */
	private function evaluate_pending_payment_entry(array $entry ): void
	{
		if ( ! $this->has_payment_been_pending_too_long( $entry ) ) {
			return;
		}

		( new PaymentController() )->post_payment_pending_expired(
			$entry,
			array(
				'payment_status' => $entry['payment_status'],
			)
		);
	}

	/**
	 * Checks if the payment for an entry has been pending for too long.
	 */
	private function has_payment_been_pending_too_long(array $entry ): bool
	{
		if ( 'Pending' !== $entry['payment_status'] ) {
			return false;
		}

		$payment_date = $entry['payment_date'];

		if ( empty( $payment_date ) ) {
			return false;
		}

		$payment_date_time = ( new DateTime( $payment_date ) )->setTimezone( new DateTimeZone( wp_timezone_string() ) );
		$current_date_time = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );

		$time_difference = $payment_date_time->diff( $current_date_time );

		return 1 <= $time_difference->h || 1 <= $time_difference->days;
	}
}
