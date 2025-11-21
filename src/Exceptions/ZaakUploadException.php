<?php
/**
 * Zaak upload exception.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Exceptions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exception thrown when a Zaak upload process fails.
 *
 * @since   1.0.0
 */
class ZaakUploadException extends ZaakException
{
	public function __construct(string $message = 'Failed to process Zaak uploads.', int $statusCode = 400 )
	{
		parent::__construct( $message, $statusCode );
	}
}
