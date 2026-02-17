<?php

declare(strict_types=1);

/**
 * Action Scheduler Controller.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\Transactions\Controllers\TransactionController;
use GFAPI;
use OWCGravityFormsZGW\LoggerZGW;
use Throwable;
use OWCGravityFormsZGW\ContainerResolver;
use InvalidArgumentException;
use RuntimeException;

/**
 * Action Scheduler Controller.
 *
 * @since NEXT
 */
class ActionSchedulerController
{
	private const DELAY_ZAAK_CREATION_SECONDS = 5; // Delay in seconds to ensure transaction is created first.

	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	public function schedule_single_actions(array $entry, array $form ): void
	{
		if ( ! FormUtils::is_form_zgw( $form ) ) {
			return;
		}

		$args = array(
			'entry_id' => (int) rgar( $entry, 'id' ),
			'form_id'  => (int) rgar( $form, 'id' ),
		);

		// Use form-specific group to prevent conflicts when multiple forms are used.
		$group = sprintf( '%s-%d', OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_GROUP, $args['form_id'] );

		if ( ! as_has_scheduled_action( OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_TRANSACTION, $args, $group ) ) {
			as_schedule_single_action( time(), OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_TRANSACTION, $args, $group );
		}

		if ( ! as_has_scheduled_action( OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_ZAAK, $args, $group ) ) {
			as_schedule_single_action( time() + self::DELAY_ZAAK_CREATION_SECONDS, OWC_GRAVITYFORMS_ZGW_ACTION_SCHEDULER_HOOK_ZAAK, $args, $group );
		}
	}

	public function handle_zaak_creation( int $entry_id, int $form_id ): void
	{
		try {
			[$entry, $form] = $this->get_entry_and_form( $entry_id, $form_id );
		} catch ( Throwable $e ) {
			$this->logger->error(
				'Error fetching entry/form for zaak creation',
				array(
					'entry_id' => $entry_id,
					'form_id'  => $form_id,
					'error'    => $e->getMessage(),
				)
			);

			return;
		}

		( new ZaakController() )->handle( $entry, $form );
	}

	public function handle_transaction( int $entry_id, int $form_id ): void
	{
		try {
			[$entry, $form] = $this->get_entry_and_form( $entry_id, $form_id );
		} catch ( Throwable $e ) {
			$this->logger->error(
				'Error fetching entry/form for transaction processing',
				array(
					'entry_id' => $entry_id,
					'form_id'  => $form_id,
					'error'    => $e->getMessage(),
				)
			);

			return;
		}

		( new TransactionController() )->create( $entry, $form );
	}

	private function get_entry_and_form(int $entry_id, int $form_id ): array
	{
		if ( $entry_id <= 0 || $form_id <= 0 ) {
			throw new InvalidArgumentException( 'Missing entry_id/form_id' );
		}

		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			throw new RuntimeException( 'Entry not found: ' . $entry_id );
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) || array() === $form ) {
			throw new RuntimeException( 'Form not found: ' . $form_id );
		}

		return array( $entry, $form );
	}
}
