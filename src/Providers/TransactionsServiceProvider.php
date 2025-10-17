<?php
/**
 * Register transactions service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Providers;

use OWCGravityFormsZGW\Transactions\TransactionCleaner;
use OWCGravityFormsZGW\Transactions\TransactionMailer;
use OWCGravityFormsZGW\Transactions\TransactionPostType;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Register transactions service provider.
 *
 * @since 1.0.0
 */
class TransactionsServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		add_action( 'init', array( $this, 'register_post_type' ) );

		new TransactionPostType();

		$this->schedule_cron_events();
	}

	public function schedule_cron_events(): void
	{
		// Schedule the transaction report event if not already scheduled.
		if ( ! wp_next_scheduled( 'owc_zgw_transaction_report' ) ) {
			wp_schedule_event( time(), 'daily', 'owc_zgw_transaction_report' );
		}
		add_action( 'owc_zgw_transaction_report', array( $this, 'handle_transaction_report' ) );

		// Schedule the cleaner event if not already scheduled.
		if ( ! wp_next_scheduled( 'owc_zgw_transaction_cleaner' )) {
			wp_schedule_event( time(), 'daily', 'owc_zgw_transaction_cleaner' );
		}
		add_action( 'owc_zgw_transaction_cleaner', array( $this, 'handle_transaction_cleaner' ) );
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type(): void
	{
		register_post_type(
			'owc_zgw_transaction',
			array(
				'labels'             => array(
					'name'               => __( 'Transacties', 'owc-gravityforms-zgw' ),
					'singular_name'      => __( 'Transactie', 'owc-gravityforms-zgw' ),
					'not_found'          => __( 'Geen transacties gevonden', 'owc-gravityforms-zgw' ),
					'not_found_in_trash' => __( 'Geen transaction gevonden in de prullenbak.', 'owc-gravityforms-zgw' ),
				),
				'menu_icon'          => 'dashicons-visibility',
				'public'             => false,
				'publicly_queryable' => false,
				'has_archive'        => false,
				'supports'           => array(),
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => false,
				'capabilities'       => self::get_capabilities(),
				'map_meta_cap'       => true,
			)
		);
	}

	/**
	 * Get capabilities for this post type.
	 */
	public static function get_capabilities(): array
	{
		return array(
			'edit_post'              => 'edit_owc_zgw_transaction',
			'read_post'              => 'read_owc_zgw_transaction',
			'delete_post'            => 'delete_owc_zgw_transaction',
			'edit_posts'             => 'edit_owc_zgw_transactions',
			'edit_others_posts'      => 'edit_others_owc_zgw_transactions',
			'publish_posts'          => 'publish_owc_zgw_transactions',
			'read_private_posts'     => 'read_private_owc_zgw_transactions',
			'delete_posts'           => 'delete_owc_zgw_transactions',
			'delete_private_posts'   => 'delete_private_owc_zgw_transactions',
			'delete_published_posts' => 'delete_published_owc_zgw_transactions',
			'delete_others_posts'    => 'delete_others_owc_zgw_transactions',
			'edit_private_posts'     => 'edit_private_owc_zgw_transactions',
			'edit_published_posts'   => 'edit_published_owc_zgw_transactions',
			'create_posts'           => 'do_not_allow',
		);
	}

	/**
	 * Handle sending the transaction report.
	 */
	public function handle_transaction_report(): void
	{
		$default_email = '__unset__';
		$to            = apply_filters( 'owc_zgw_transaction_report_recipient_email', $default_email );

		$mailer = new TransactionMailer();
		$mailer->send_report( $default_email, $to );
	}

	/**
	 * Handle cleaning old transactions.
	 */
	public function handle_transaction_cleaner(): void
	{
		$days = apply_filters( 'owc_zgw_transaction_cleanup_days', 30 );

		$cleaner = new TransactionCleaner();
		$cleaner->delete_old_transactions( (int) $days );
	}
}
