<?php

declare(strict_types=1);

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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWC\ZGW\Http\Response;
use OWCGravityFormsZGW\Contracts\AbstractDeleteZaakAction;

/**
 * Delete Zaak action.
 *
 * @since SINCE
 */
class DeleteZaakAction extends AbstractDeleteZaakAction
{
	public function delete( string $identifier ): Response
	{
		return $this->client->zaken()->delete( $identifier );
	}
}
