<?php

declare(strict_types=1);

/**
 * Create Zaak action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Actions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Contracts\AbstractCreateZaakAction;
use OWC\ZGW\Entities\Zaak;

/**
 * Create Zaak action.
 *
 * @since 1.0.0
 */
class CreateZaakAction extends AbstractCreateZaakAction
{
	public function create(): Zaak
	{
		$args = $this->get_mapped_required_zaak_creation_args();
		$zaak = $this->client->zaken()->create( new Zaak( $args, $this->client ) );

		$this->add_created_zaak_as_entry_meta( $zaak );
		$this->add_rol_to_zaak( $zaak );
		$this->create_zaak_properties( $zaak );

		return $zaak;
	}
}
