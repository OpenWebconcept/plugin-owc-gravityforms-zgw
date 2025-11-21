<?php
/**
 * Adapter for informatieobjecttypen.
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
use OWC\ZGW\Entities\Attributes\Confidentiality;
use OWC\ZGW\Entities\Informatieobjecttype;

/**
 * Adapter for informatieobjecttypen.
 *
 * @since 1.0.0
 */
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
					$designation = $objecttype->vertrouwelijkheidaanduiding ?? ( $objecttype->vertrouwelijkheidsaanduiding ?? '' );

					if ( $designation instanceof Confidentiality ) {
						$designation = $designation->name ?? '';
					}

					if ( ! is_string( $designation ) || 1 > strlen( $designation ) ) {
						$designation = 'Aanduiding onbekend';
					}

					return array(
						'name'  => $objecttype->url,
						'label' => "{$objecttype->omschrijving} ({$designation})",
						'value' => $objecttype->url,
					);
				},
				'No information object types found.'
			);
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );

			return $this->handle_no_choices( 'informatieobjecttypen' );
		}
	}
}
