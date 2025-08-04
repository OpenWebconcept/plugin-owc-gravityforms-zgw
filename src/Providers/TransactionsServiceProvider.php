<?php
/**
 * Register transactions service provider.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Providers;

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
		add_action( 'init', array( $this, 'register_post_statuses' ) );

		new TransactionPostType();
	}

	/**
	 * Register the post type.
	 */
	public function register_post_type(): void
	{
		register_post_type(
			'owc_zgw_transaction',
			array(
				'labels'       => array(
					'name'               => __( 'Transactions', 'owc-gravityforms-zgw' ),
					'singular_name'      => __( 'Transaction', 'owc-gravityforms-zgw' ),
					'not_found'          => __( 'No transactions found.', 'owc-gravityforms-zgw' ),
					'not_found_in_trash' => __( 'No transactions found in Trash.', 'owc-gravityforms-zgw' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'show_in_rest' => true,
				'capabilities' => array(
					'create_posts' => false,
				),
			)
		);
	}

	/**
	 * Register the post statuses.
	 */
	public function register_post_statuses(): void
	{
		register_post_status(
			'payment_pending',
			array(
				'label'                     => _x( 'Pending', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count value */
				'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'payment_pending',
			array(
				'label'                     => _x( 'Completed', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count value */
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'payment_pending',
			array(
				'label'                     => _x( 'Failed', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count value */
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);
	}

	/**
	 * Get the transaction states.
	 */
	public static function get_transaction_states(): array
	{
		return array(
			'transaction_pending'   => _x( 'Pending', 'Transaction status', 'owc-gravityforms-zgw' ),
			'transaction_completed' => _x( 'Completed', 'Transaction status', 'owc-gravityforms-zgw' ),
			'transaction_failed'    => _x( 'Failed', 'Transaction status', 'owc-gravityforms-zgw' ),
		);
	}
}
