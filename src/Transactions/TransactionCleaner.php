<?php
/**
 * Transaction Cleaner.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Transactions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use WP_Query;

/**
 * Transaction Cleaner.
 *
 * @since 1.0.0
 */
class TransactionCleaner
{
	private $queryFactory;

	public function __construct(callable $queryFactory = null )
	{
		// default factory uses real WP_Query
		$this->queryFactory = $queryFactory ?: function (array $args ) {
			return new WP_Query( $args );
		};
	}

	/**
	 * Delete transactions older than the given number of days.
	 */
	public function delete_old_transactions(int $days = 30 ): void
	{
		$threshold = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$args = array(
			'post_type'      => 'owc_zgw_transaction',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'transaction_datetime',
					'value'   => $threshold,
					'compare' => '<',
					'type'    => 'DATETIME',
				),
			),
		);

		$query = call_user_func( $this->queryFactory, $args );

		foreach ($query->posts as $post_id) {
			\wp_delete_post( $post_id, true );
		}

		\wp_reset_postdata();
	}
}
