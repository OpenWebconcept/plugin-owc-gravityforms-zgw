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

use GFAPI;
use GFForms;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Transaction Post Type.
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
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', $this->custom_columns( ... ), 10, 2 );

		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_action( 'admin_notices', $this->admin_notice( ... ) );

		add_action(
			'admin_enqueue_scripts',
			function () {
				$plugin_root_path = trailingslashit( dirname( plugin_dir_path( __FILE__ ), 2 ) );
				$plugin_root_url  = trailingslashit( dirname( plugin_dir_url( __FILE__ ), 2 ) );

				$rel        = 'assets/style-admin.css';
				$style_path = $plugin_root_path . $rel;
				$style_url  = $plugin_root_url . $rel;

				wp_enqueue_style(
					'owc-gravityforms-zgw-admin-style',
					$style_url,
					array(),
					file_exists( $style_path ) ? filemtime( $style_path ) : null
				);
			}
		);
	}

	/**
	 * Set admin notice.
	 */
	public function admin_notice(): void
	{
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) || $screen->id !== 'edit-' . self::POST_TYPE ) {
			return;
		}

		$class   = 'notice notice-info';
		$message = __( 'This page tracks Zaaksysteem Transactions via Gravity Forms and reports their status back here.', 'Transaction status', 'owc-gravityforms-zgw' );

		printf(
			'<div class="notice notice-%1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}

	/**
	 * Register custom post statuses to the post type.
	 */
	public function register_post_statuses(): void
	{
		register_post_status(
			'transaction_success',
			array(
				'label'       => _x( 'Success', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'Success <span class="count">(%s)</span>', 'Success <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'transaction_pending',
			array(
				'label'       => _x( 'Pending', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'transaction_failed',
			array(
				'label'       => _x( 'Failed', 'Transaction status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);
	}

	/**
	 * Columns.
	 */
	public function columns( array $columns ): array
	{
		$columns = array(
			'transaction_status'   => sprintf(
				'<span>%s</span>',
				esc_html__( 'Status', 'owc-gravityforms-zgw' ),
				esc_html__( 'Status', 'owc-gravityforms-zgw' )
			),
			'transaction_form_id'  => sprintf(
				'<span>%s</span>',
				esc_html__( 'Form ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Form ID', 'owc-gravityforms-zgw' )
			),
			'transaction_entry_id' => sprintf(
				'<span>%s</span>',
				esc_html__( 'Entry ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Entry ID', 'owc-gravityforms-zgw' )
			),
			'transaction_zaak_id'  => sprintf(
				'<span>%s</span>',
				esc_html__( 'Zaak ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Zaak ID', 'owc-gravityforms-zgw' )
			),
			'transaction_message'  => __( 'Message', 'owc-gravityforms-zgw' ),
			'transaction_datetime' => __( 'Datetime', 'owc-gravityforms-zgw' ),
		);

		return $columns;
	}

	/**
	 * Sortable columns.
	 */
	public function sortable_columns( array $sortable_columns ): array
	{
		$sortable_columns['transaction_form_id']  = 'ID';
		$sortable_columns['transaction_entry_id'] = 'ID';
		$sortable_columns['transaction_zaak_id']  = 'ID';
		$sortable_columns['transaction_datetime'] = 'date';

		return $sortable_columns;
	}

	public static function get_post_status_css_class( $post_status ): string
	{
		return match ( $post_status ) {
			'transaction_success', => 'owc-gravityforms-zgw-transaction-icon-success',
			'transaction_failed', => 'owc-gravityforms-zgw-transaction-icon-failed',
			default => 'owc-gravityforms-zgw-transaction-icon-pending',
		};
	}

	/**
	 * Custom columns.
	 */
	public function custom_columns( string $column, int $post_id ): void
	{
		switch ( $column ) {
			case 'transaction_status':
				$post_status = get_post_status( $post_id );

				if ( false === $post_status ) {
					break;
				}

				$label = __( 'Unknown', 'owc-gravityforms-zgw' );

				if ( 'trash' === $post_status ) {
					$post_status = get_post_meta( $post_id, '_wp_trash_meta_status', true );
				}

				$status_object = get_post_status_object( $post_status );

				if ( isset( $status_object, $status_object->label ) ) {
					$label = $status_object->label;
				}

				printf(
					'<span class="owc-gravityforms-zgw-transaction-icon %s" title="%s">%s</span>',
					esc_attr( $this->get_post_status_css_class( $post_status ) ),
					esc_attr( $label ),
					esc_html( $label )
				);

				break;
			case 'transaction_form_id':
				$form_id = get_post_meta( $post_id, 'transaction_form_id', true );

				if ( $form_id ) {
					$url = add_query_arg(
						array(
							'page' => 'gf_edit_forms',
							'id'   => absint( $form_id ),
						),
						admin_url( 'admin.php' )
					);

					printf(
						'<a href="%s">%s</a>',
						esc_url( $url ),
						esc_html( $form_id )
					);
				}

				break;
			case 'transaction_entry_id':
				$entry_id = get_post_meta( $post_id, 'transaction_entry_id', true );
				echo FormUtils::get_link_to_form_entry( $entry_id );

				break;
			case 'transaction_zaak_id':
				echo esc_html( get_post_meta( $post_id, 'transaction_zaak_id', true ) );
				break;
			case 'transaction_message':
				echo esc_html( get_post_meta( $post_id, 'transaction_message', true ) );
				break;
			case 'transaction_datetime':
				echo esc_html( get_post_meta( $post_id, 'transaction_datetime', true ) );
				break;
		}
	}
}
