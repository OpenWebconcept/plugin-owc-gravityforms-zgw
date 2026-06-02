<?php

declare(strict_types=1);

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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\Traits\LocalizeMetaDateTime;

/**
 * Transaction Post Type.
 *
 * @since 1.0.0
 */
class TransactionPostType
{
	use LocalizeMetaDateTime;

	public const POST_TYPE = 'owc_zgw_transaction';

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

		if ( ! isset( $screen->id ) || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}

		$class   = 'notice notice-info';
		$message = __( 'Overzicht van zaaksysteemtransacties vanuit Gravity Forms formulieren welke gekoppeld zijn met een zaaksysteem.', 'owc-gravityforms-zgw' );

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
				'label'       => _x( 'Voltooid', 'Transactie status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'Voltooid <span class="count">(%s)</span>', 'Voltooid <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'transaction_pending',
			array(
				'label'       => _x( 'In behandeling', 'Transactie status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'In behandeling <span class="count">(%s)</span>', 'In behandeling <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
			)
		);

		register_post_status(
			'transaction_failed',
			array(
				'label'       => _x( 'Mislukt', 'Transactie status', 'owc-gravityforms-zgw' ),
				'public'      => true,
				/* translators: %s: count value */
				'label_count' => _n_noop( 'Mislukt <span class="count">(%s)</span>', 'Mislukt <span class="count">(%s)</span>', 'owc-gravityforms-zgw' ),
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
				esc_html__( 'Formulier ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Formulier ID', 'owc-gravityforms-zgw' )
			),
			'transaction_entry_id' => sprintf(
				'<span>%s</span>',
				esc_html__( 'Inzending ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Inzending ID', 'owc-gravityforms-zgw' )
			),
			'transaction_zaak_id'  => sprintf(
				'<span>%s</span>',
				esc_html__( 'Zaak ID', 'owc-gravityforms-zgw' ),
				esc_html__( 'Zaak ID', 'owc-gravityforms-zgw' )
			),
			'transaction_message'  => __( 'Melding', 'owc-gravityforms-zgw' ),
			'transaction_datetime' => __( 'Datumtijd', 'owc-gravityforms-zgw' ),
			'transaction_actions'  => __( 'Acties', 'owc-gravityforms-zgw' ),
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
	 * Renders a single column cell and returns the HTML as a string.
	 */
	public static function render_column( string $column, int $post_id ): string
	{
		ob_start();
		( new self() )->custom_columns( $column, $post_id );
		return (string) ob_get_clean();
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

				$label = __( 'Onbekend', 'owc-gravityforms-zgw' );

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
				echo FormUtils::get_link_to_form_entry( (int) $entry_id );

				break;
			case 'transaction_zaak_id':
				echo esc_html( get_post_meta( $post_id, 'transaction_zaak_id', true ) );

				break;
			case 'transaction_message':
				$message           = (string) get_post_meta( $post_id, 'transaction_message', true );
				$pending_deletions = array_filter( (array) get_post_meta( $post_id, 'transaction_pending_deletions', true ) );
				$pending_count     = count( $pending_deletions );

				if ( $message ) {
					printf(
						'<button type="button" class="owc-btn-toggle-details" data-label-show="%1$s" data-label-hide="%2$s">%1$s</button><div class="owc-transaction-details" hidden>%3$s</div>',
						esc_attr__( 'Toon melding', 'owc-gravityforms-zgw' ),
						esc_attr__( 'Verberg melding', 'owc-gravityforms-zgw' ),
						nl2br( esc_html( $message ) )
					);
				}

				if ( $pending_count > 0 ) {
					/* translators: %d is the number of zaaks waiting to be deleted. */
					$pending_label       = esc_html( sprintf( _n( '%d verwijdering in afwachting', '%d verwijderingen in afwachting', $pending_count, 'owc-gravityforms-zgw' ), $pending_count ) );
					$pending_tooltip     = esc_attr__( 'Een herpoging maakt een nieuwe zaak aan. Bij succes wordt de originele zaak verwijderd. Bij mislukking wordt de nieuwe zaak verwijderd. Als die verwijdering mislukt, worden de zaken hier bijgehouden.', 'owc-gravityforms-zgw' );
					$pending_description = esc_html__( 'Bij een herpoging wordt een nieuwe zaak aangemaakt. Als de herpoging slaagt, wordt de originele zaak verwijderd. Als de herpoging mislukt, wordt de nieuw aangemaakte zaak verwijderd. De verwijdering van de onderstaande zaken is mislukt — ze zijn niet meer in gebruik maar staan nog in het zaaksysteem. De verwijdering wordt automatisch opnieuw geprobeerd bij de volgende herpoging.', 'owc-gravityforms-zgw' );
					$pending_items       = implode( '', array_map( fn( $uuid ) => sprintf( '<li><code>%s</code></li>', esc_html( $uuid ) ), $pending_deletions ) );

					printf(
						'<button type="button" class="owc-btn-toggle-details owc-btn-pending-deletions" data-label-show="%1$s" data-label-hide="%1$s" title="%2$s">%3$s</button><div class="owc-transaction-details owc-pending-deletions-details" hidden><p class="owc-pending-deletions-description">%4$s</p><ul class="owc-pending-deletions-list">%5$s</ul></div>',
						esc_attr( $pending_label ),
						$pending_tooltip,
						$pending_label,
						$pending_description,
						$pending_items
					);
				}

				break;
			case 'transaction_datetime':
				echo esc_html( $this->localize_meta_datetime( $post_id, 'transaction_datetime' ) );

				break;
			case 'transaction_actions':
				$post_status    = get_post_status( $post_id );
				$action_content = get_post_meta( $post_id, 'transaction_actions', true );
				$entry_id       = get_post_meta( $post_id, 'transaction_entry_id', true );

				if ( 'transaction_failed' === $post_status && $action_content ) {
					printf(
						'<button type="button" class="owc-gravityforms-zgw-btn-retry" data-entry-id="%d"><img src="%s" alt="Retry" /></button>',
						esc_attr( $entry_id ),
						esc_url( (string) $action_content )
					);
				}

				break;
		}
	}
}
