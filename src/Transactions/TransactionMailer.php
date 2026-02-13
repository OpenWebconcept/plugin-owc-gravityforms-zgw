<?php

declare(strict_types=1);

/**
 * Transaction Mailer.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Transactions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\GravityForms\FormUtils;
use WP_Query;

/**
 * Transaction Mailer.
 *
 * @since 1.0.0
 */
class TransactionMailer
{
	private $queryFactory;

	public function __construct($queryFactory = null ) {
		$this->queryFactory = $queryFactory ?: function ($args ) {
			return new \WP_Query( $args );
		};
	}

	public function send_report($default_email, $to ): void
	{
		$yesterday = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		$now       = current_time( 'mysql' );

		$all_statuses         = get_post_stati( array( 'internal' => false ) );
		$not_success_statuses = array_filter(
			$all_statuses,
			function ($status ) {
				return $status !== 'transaction_success';
			}
		);

		$args = array(
			'post_type'      => 'owc_zgw_transaction',
			'post_status'    => $not_success_statuses,
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'transaction_datetime',
					'value'   => array( $yesterday, $now ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				),
			),
		);

		$query = call_user_func( $this->queryFactory, $args );

		if ( $query->have_posts() ) {
			$table  = '<table>';
			$table .= '<thead><tr>
                <th>' . __( 'Transaction ID', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Status', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Entry ID', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Datetime', 'owc-gravityforms-zgw' ) . '</th>
            </tr></thead><tbody>';

			foreach ( $query->posts as $post ) {
				$entry_id = get_post_meta( $post->ID, 'transaction_entry_id', true );
				$table   .= sprintf(
					'<tr>
                        <td>%d</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                    </tr>',
					esc_html( $post->ID ),
					esc_html( $post->post_status ),
					FormUtils::get_link_to_form_entry( (int) $entry_id ) ?: 'N/A',
					esc_html( get_post_meta( $post->ID, 'transaction_datetime', true ) )
				);
			}

			$table .= '</tbody></table>';

			if ( $to !== $default_email && is_email( $to ) ) {
				$subject = __( 'Zaaksysteem Failed Transactions Report', 'owc-gravityforms-zgw' );
				$message = __( 'The following transactions had failures:', 'owc-gravityforms-zgw' ) . "<br><br>" . $table;
				wp_mail( $to, $subject, $message );
			}
		}

		wp_reset_postdata();
	}
}
