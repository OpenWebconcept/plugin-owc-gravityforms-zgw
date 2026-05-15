<?php

declare(strict_types=1);

/**
 * Notification controller.
 *
 * This controller is responsible for controlling when GravityForms notifications are sent,
 * ensuring they are only dispatched at the appropriate moment (e.g. after a 'Zaak' is created).
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFCommon;
use GFAPI;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Notification controller.
 *
 * @since NEXT
 */
class NotificationController
{
	/**
	 * Disable notifications for ZGW forms so they can be sent manually at the right moment.
	 */
	public function disable_notifications( bool $is_disabled, array $notification, array $form, array $entry ): bool
	{
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return $is_disabled;
		}

		return true;
	}

	/**
	 * Send all active notifications for the given form entry.
	 */
	public function send_notifications( array $entry, array $form ): void
	{
		$notifications = $form['notifications'] ?? array();

		if ( ! is_array( $notifications ) || array() === $notifications ) {
			return;
		}

		$notifications = array_filter(
			$notifications,
			function ( array $notification ) {
				return ! isset( $notification['isActive'] ) || $notification['isActive'];
			}
		);

		if ( ! is_array( $notifications ) || array() === $notifications ) {
			return;
		}

		$lead = GFAPI::get_entry( $entry['id'] );

		GFCommon::send_notifications( array_keys( $notifications ), $form, $lead );
	}
}
