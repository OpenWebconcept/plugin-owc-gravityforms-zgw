<?php

declare(strict_types=1);

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

use Throwable;

/**
 * Exception thrown when a Zaak upload process fails.
 *
 * @since   1.0.0
 */
class ZaakUploadException extends ZaakException
{
	/**
	 * @since 1.0.0
	 * @since NEXT Added the $previous parameter.
	 */
	public function __construct( string $message = 'Failed to process Zaak uploads.', int $statusCode = 400, ?Throwable $previous = null )
	{
		parent::__construct( $message, $statusCode, $previous );
	}
}
