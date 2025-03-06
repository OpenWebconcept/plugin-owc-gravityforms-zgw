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
use OWC\ZGW\Entities\Informatieobjecttype;

class InformatieobjecttypeAdapter extends Adapter
{
	/**
	 * @since 1.0.0
	 */
	public function handle(): array
	{
		try {
			return $this->get_types(
				sprintf( '%s-form-settings-information-object-type', $this->transient_key_prefix() ), // Unique transient key.
				'informatieobjecttypen',
				function (Informatieobjecttype $objecttype ) {
					return array(
						'name'  => $objecttype->url,
						'label' => "{$objecttype->omschrijving} ({$objecttype->vertrouwelijkheidaanduiding})",
						'value' => $objecttype->url,
					);
				},
				'No information object typen found.'
			);
		} catch (Exception $e) {
			return $this->handle_no_choices( 'informatieobjecttypen' );
		}
	}
}
