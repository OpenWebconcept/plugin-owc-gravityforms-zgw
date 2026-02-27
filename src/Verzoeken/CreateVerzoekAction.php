<?php
/**
 * Action to create a ZGW Verzoek upon Gravity Forms submission.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;

/**
 * Action to create a ZGW Verzoek upon Gravity Forms submission.
 *
 * @since NEXT
 */
class CreateVerzoekAction extends AbstractCreateVerzoekAction
{
	public function create(?array $uploads = null): array
	{
		$args    = $this->get_mapped_required_zaak_creation_args($uploads);
		$verzoek = $this->create_object( $args );

		$this->add_created_verzoek_as_entry_meta( $verzoek );

		return $verzoek;
	}

	public function create_object(array $args ): array
	{
		$verzoek = ( new Client() )->create_object( $args );

		if ( ! isset( $verzoek['uuid'] ) ) {
			throw new Exception( 'Failed to create ZGW Verzoek: missing UUID in response' );
		}

		return $verzoek;
	}
}
