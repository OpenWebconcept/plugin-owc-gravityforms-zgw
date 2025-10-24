<?php
/**
 * Transaction Controller.
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

use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Transaction Controller.
 *
 * @since 1.0.0
 */
class TransactionController
{
	const POST_TYPE = 'owc_zgw_transaction';

	private array $statuses;

	public function __construct()
	{
		$this->statuses = array(
			TransactionStatus::SUCCESS => 'transaction_success',
			TransactionStatus::PENDING => 'transaction_pending',
			TransactionStatus::FAILED  => 'transaction_failed',
		);
	}

	/**
	 * Create transaction.
	 */
	public function create( array $entry, array $form ): void
	{
		// Only create transaction for ZGW enabled forms.
		if ( ! FormUtils::is_form_zgw( $form )) {
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => sprintf( 'Entry %d (Form %d)', $entry['id'], $entry['form_id'] ),
				'post_status' => $this->statuses[ TransactionStatus::PENDING ],
				'meta_input'  => $this->add_metadata( $entry ),
			)
		);

		// Save reference to entry so other processes can find the post.
		if ( $post_id > 0 ) {
			gform_update_meta( $entry['id'], 'transaction_post_id', $post_id );
		}
	}

	private static function add_metadata( $entry ): array
	{
		return array(
			'transaction_form_id'  => $entry['form_id'],
			'transaction_entry_id' => $entry['id'],
			'transaction_datetime' => $entry['date_created'],
		);
	}
}
