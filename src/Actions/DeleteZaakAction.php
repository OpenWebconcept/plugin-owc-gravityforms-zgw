<?php
/**
 * Delete Zaak action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   SINCE
 */

namespace OWCGravityFormsZGW\Actions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCGravityFormsZGW\Contracts\AbstractDeleteZaakAction;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Http\Response;

/**
 * Delete Zaak action.
 *
 * @since SINCE
 */
class DeleteZaakAction extends AbstractDeleteZaakAction
{
	public function delete(string $identifier ): Response
	{
		return $this->client->zaken()->delete( $identifier );
	}
}
