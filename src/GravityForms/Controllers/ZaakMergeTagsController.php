<?php

declare(strict_types=1);

/**
 * Zaak merge tags controller.
 *
 * Registers custom merge tags that resolve to zaak-specific values stored
 * in entry metadata. These can be used anywhere Gravity Forms processes merge
 * tags, including Gravity PDF template footers and headers.
 *
 * Supported merge tags:
 *   {zaak_id} — the identificatie (ID) of the zaak created for this entry.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\GravityForms\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFAPI;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\LoggerZGW;

/**
 * Zaak merge tags controller.
 *
 * @since NEXT
 */
class ZaakMergeTagsController
{
	protected LoggerZGW $logger;

	public function __construct()
	{
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	/**
	 * Replace zaak-specific merge tags with their entry meta values.
	 * Scoped to ZGW-enabled forms to avoid interfering with other forms.
	 */
	public function replace_zaak_merge_tags(
		string $text,
		mixed $form,
		mixed $entry,
		bool $url_encode,
		bool $esc_html,
		bool $nl2br,
		string $format
	): string {
		if ( ! is_array( $form ) || ! is_array( $entry ) || ! FormUtils::is_form_zgw( $form ) ) {
			return $text;
		}

		if ( strpos( $text, '{zaak_id}' ) === false || ! isset( $entry['id'] ) ) {
			return $text;
		}

		$transaction_post_id = gform_get_meta( $entry['id'], 'transaction_post_id' );
		$zaak_id             = is_numeric( $transaction_post_id ) ? (string) get_post_meta( (int) $transaction_post_id, 'transaction_zaak_id', true ) : '';

		if ( '' === $zaak_id ) {
			$this->logger->warning(
				'Zaak ID merge tag found but no ID available for entry.',
				array(
					'entry_id' => $entry['id'] ?? null,
				)
			);
		}

		if ( $url_encode ) {
			$zaak_id = urlencode( $zaak_id );
		} elseif ( $esc_html ) {
			$zaak_id = esc_html( $zaak_id );
		}

		return str_replace( '{zaak_id}', $zaak_id, $text );
	}

	/**
	 * Register zaak merge tags in the merge tag picker shown above WYSIWYG editors.
	 * Only added for ZGW-enabled forms so the tag does not appear on unrelated forms.
	 */
	public function add_zaak_merge_tags( array $tags, mixed $form_id ): array
	{
		if ( ! is_numeric( $form_id ) ) {
			return $tags;
		}

		$form = GFAPI::get_form( (int) $form_id );

		if ( ! is_array( $form ) || ! FormUtils::is_form_zgw( $form ) ) {
			return $tags;
		}

		$tags[] = array(
			'tag'   => '{zaak_id}',
			'label' => __( 'Zaak ID', 'owc-gravityforms-zgw' ),
		);

		return $tags;
	}
}
