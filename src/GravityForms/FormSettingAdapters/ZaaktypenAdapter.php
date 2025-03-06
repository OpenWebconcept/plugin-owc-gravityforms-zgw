<?php

declare(strict_types=1);

namespace OWCGravityFormsZGW\GravityForms\FormSettingAdapters;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Exception;
use OWC\ZGW\Entities\Zaaktype;

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
					return array(
						'name'  => $zaaktype->identificatie,
						'label' => "{$zaaktype->omschrijving} ({$zaaktype->identificatie})",
						'value' => $zaaktype->url, // -> when the api supports filtering on zaaktype identification this line should be updated to $zaaktype->identificatie.
					);
				},
				'No zaaktypen found.'
			);
		} catch (Exception $e) {
			return $this->handle_no_choices( 'zaaktypen' );
		}
	}
}
