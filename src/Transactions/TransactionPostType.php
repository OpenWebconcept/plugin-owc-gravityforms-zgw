<?php
/**
 * Transaction Post Type.
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

/**
 * Settings.
 *
 * @since 1.0.0
 */
class TransactionPostType
{
	const POST_TYPE = 'owc_zgw_transaction';

	public function __construct()
	{
		add_filter( 'manage_edit-' . self::POST_TYPE . '_columns', array( $this, 'columns' ) );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Columns.
	 */
	public function columns( array $columns ): array
	{
		$columns = array(
			'cb'                      => '<input type="checkbox" />',
			'transaction_status'      => sprintf(
				'<span class="owc-g-zgw-icon" title="%s">%s</span>',
				esc_html__( 'Status', 'owc-gravityforms-zgw' ),
				esc_html__( 'Status', 'owc-gravityforms-zgw' )
			),
			'transaction_description' => sprintf(
				'<span>%s</span>',
				esc_html__( 'Description', 'owc-gravityforms-zgw' ),
				esc_html__( 'Description', 'owc-gravityforms-zgw' )
			),
			'transaction_message'     => __( 'Message', 'owc-gravityforms-zgw' ),
			'transaction_datetime'    => __( 'Datetime', 'owc-gravityforms-zgw' ),
		);

		return $columns;
	}

	/**
	 * Sortable columns.
	 */
	public function sortable_columns( array $sortable_columns ): array
	{
		$sortable_columns['transaction_description'] = 'ID';
		$sortable_columns['transaction_datetime']    = 'date';

		return $sortable_columns;
	}
}
