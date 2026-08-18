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
use OWCGravityFormsZGW\Traits\LocalizeMetaDateTime;
use WP_Query;

/**
 * Transaction Mailer.
 *
 * @since 1.0.0
 */
class TransactionMailer
{
	use LocalizeMetaDateTime;

	private $queryFactory;

	public function __construct( $queryFactory = null ) {
		$this->queryFactory = $queryFactory ?: function ( $args ) {
			return new WP_Query( $args );
		};
	}

	public function send_report( string $default_email, string $to ): void
	{
		$yesterday = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		$now       = current_time( 'mysql' );

		$all_statuses         = get_post_stati( array( 'internal' => false ) );
		$not_success_statuses = array_filter(
			$all_statuses,
			function ( $status ) {
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
                <th>' . __( 'Transactie ID', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Status', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Inzending ID', 'owc-gravityforms-zgw' ) . '</th>
                <th>' . __( 'Datumtijd', 'owc-gravityforms-zgw' ) . '</th>
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
					esc_html( $this->localize_meta_datetime( $post->ID, 'transaction_datetime' ) )
				);
			}

			$table .= '</tbody></table>';

			$recipients = array_filter( array_map( 'trim', explode( ',', $to ) ), 'is_email' );

			if ( $to !== $default_email && array() !== $recipients ) {
				$subject = __( 'Zaaksysteem gefaalde transacties rapport', 'owc-gravityforms-zgw' );
				$message = __( 'De volgende transacties zijn mislukt:', 'owc-gravityforms-zgw' ) . "<br><br>" . $table;
				wp_mail( $recipients, $subject, $message, array( 'Content-Type: text/html;' ) );
			}
		}

		wp_reset_postdata();
	}
}
