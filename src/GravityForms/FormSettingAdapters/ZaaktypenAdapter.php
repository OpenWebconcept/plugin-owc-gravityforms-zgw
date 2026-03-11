<?php

declare(strict_types=1);

/**
 * Adapter for zaaktypen.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms\FormSettingAdapters;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use OWC\ZGW\Entities\Zaaktype;

/**
 * Adapter for zaaktypen.
 *
 * @since 1.0.0
 */
class ZaaktypenAdapter extends Adapter
{
	/**
	 * @since 1.0.0
	 */
	public function handle(): array
	{
		try {
			return $this->get_types(
				sprintf( '%s-form-settings-zaaktypen', $this->transient_key_prefix() ), // Unique transient key.
				'zaaktypen',
				function (Zaaktype $zaaktype ) {
					$identification = $zaaktype->identificatie ?? '';
					$description    = $zaaktype->omschrijving ?? '';
					$url            = $zaaktype->url ?? '';

					if ( empty( $identification ) || empty( $description ) || empty( $url ) ) {
						return array();
					}

					return array(
						'name'  => $identification,
						'label' => "{$description} ({$identification})",
						'value' => $url, // @todo: when the api supports filtering on zaaktype identification this line should be updated to $identification.
					);
				},
				'No zaaktypen found.'
			);
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );

			return $this->handle_no_choices( 'zaaktypen' );
		}
	}
}
