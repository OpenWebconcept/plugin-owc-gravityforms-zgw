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
 * @since   1.11.0
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFAPI;
use GFCommon;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\LoggerZGW;

/**
 * Notification controller.
 *
 * @since 1.11.0
 */
class NotificationController
{
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	/**
	 * Disable notifications for ZGW forms so they can be sent manually after zaak creation.
	 * Hooks into gform_disable_notification (singular) which is what GF actually applies.
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

		if ( array() === $notifications ) {
			return;
		}

		// Re-fetch the entry so merge tags (e.g. {zaak_id}) resolve against the post meta written after submission.
		$lead = GFAPI::get_entry( $entry['id'] );

		if ( is_wp_error( $lead ) ) {
			$this->logger->error(
				'Failed to fetch entry when sending notifications manually',
				array(
					'entry_id' => $entry['id'],
					'error'    => $lead,
				)
			);

			return;
		}

		GFCommon::send_notifications( array_keys( $notifications ), $form, $lead );
	}
}
