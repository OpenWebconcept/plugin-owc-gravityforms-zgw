<?php

declare(strict_types=1);

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
	 * Builds the transient key used to cache the informatieobjecttypen of a supplier.
	 *
	 * @since 1.14.0
	 */
	public static function transient_key( string $supplier_name ): string
	{
		return sprintf( '%s-form-settings-information-object-type', self::key_prefix( $supplier_name ) );
	}

	/**
	 * @since 1.0.0
	 */
	public function handle(): array
	{
		try {
			return $this->get_types(
				self::transient_key( $this->supplier_name ),
				'informatieobjecttypen',
				function ( Informatieobjecttype $objecttype ) {
					$designation = $objecttype->vertrouwelijkheidaanduiding ?? ( $objecttype->vertrouwelijkheidsaanduiding ?? '' );

					if ( $designation instanceof Confidentiality ) {
						$designation = $designation->name ?? '';
					}

					if ( ! is_string( $designation ) || 1 > strlen( $designation ) ) {
						$designation = 'Aanduiding onbekend';
					}

					$description = (string) ( $objecttype->omschrijving ?? '' );
					$url         = (string) ( $objecttype->url ?? '' );

					if ( empty( $description ) || empty( $url ) ) {
						return array();
					}

					return array(
						'name'  => $url,
						'label' => "{$description} ({$designation})",
						'value' => $url,
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
