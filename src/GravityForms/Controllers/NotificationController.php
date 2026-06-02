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
	/**
	 * Entry meta key used to track which notification IDs have already been sent.
	 * Written by track_sent_notification() and read by send_notifications().
	 *
	 * @since NEXT
	 */
	private const SENT_META_KEY = 'owc_gz_sent_notification_ids';

	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	/**
	 * Disable notifications for ZGW forms so they can be sent manually after Zaak creation.
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
	 * Records every notification that is actually delivered for a ZGW form entry.
	 *
	 * Hooks into gform_notification, which fires inside GFCommon::send_notification()
	 * for every send regardless of which plugin triggered it. This means notifications
	 * sent by third-party plugins (e.g. Pronamic delayed notifications via GFCommon)
	 * are tracked here before ZGW gets a chance to send them again.
	 *
	 * Notifications sent via GFCommon are always sent, regardless if the 'gform_disable_notification' hook disabled them or not.
	 * We can rely on this hook to track all sends and prevent double sends by ZGW.
	 *
	 * @since NEXT
	 */
	public function track_sent_notification( array $notification, array $form, array $entry ): array
	{
		if ( ! FormUtils::is_form_zgw( $form ) || '' === trim( (string) ( $notification['id'] ?? '' ) ) ) {
			return $notification;
		}

		$sent = gform_get_meta( $entry['id'], self::SENT_META_KEY );
		$sent = is_array( $sent ) ? $sent : array();

		if ( ! in_array( $notification['id'], $sent, true ) ) {
			$sent[] = $notification['id'];
			gform_update_meta( $entry['id'], self::SENT_META_KEY, $sent );
		}

		return $notification;
	}

	/**
	 * Send all active notifications for the given form entry that have not yet been sent.
	 *
	 * Notifications already recorded in entry meta (e.g. sent by Pronamic's delayed
	 * notification fulfillment) are skipped to prevent double sends.
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

		// Skip notification IDs that were already sent by another plugin (e.g. Pronamic),
		// detected via the gform_notification tracking hook.
		$already_sent = gform_get_meta( $lead['id'], self::SENT_META_KEY );
		$already_sent = is_array( $already_sent ) ? $already_sent : array();

		$ids_to_send = array_values( array_diff( array_keys( $notifications ), $already_sent ) );

		if ( array() === $ids_to_send ) {
			$this->logger->info(
				'Skipping notifications: all active notifications were already sent for this entry.',
				array( 'entry_id' => $lead['id'] )
			);

			return;
		}

		GFCommon::send_notifications( $ids_to_send, $form, $lead );
	}
}
