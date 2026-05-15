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
 *   {zaak_uuid} — the UUID of the zaak created for this entry.
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

		if ( strpos( $text, '{zaak_uuid}' ) === false ) {
			return $text;
		}

		$zaak_uuid = '';

		if ( isset( $entry['id'] ) ) {
			$transaction_post_id = gform_get_meta( $entry['id'], 'transaction_post_id' );
			$zaak_uuid           = (string) get_post_meta( $transaction_post_id, 'transaction_zaak_id', true );
		}

		if ( '' === $zaak_uuid ) {
			$this->logger->warning(
				'Zaak UUID merge tag found but no UUID available for entry.',
				array(
					'entry_id' => $entry['id'] ?? null,
				)
			);
		}

		if ( $url_encode ) {
			$zaak_uuid = urlencode( $zaak_uuid );
		} elseif ( $esc_html ) {
			$zaak_uuid = esc_html( $zaak_uuid );
		}

		return str_replace( '{zaak_uuid}', $zaak_uuid, $text );
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
			'tag'   => '{zaak_uuid}',
			'label' => __( 'Zaak UUID', 'owc-gravityforms-zgw' ),
		);

		return $tags;
	}
}
