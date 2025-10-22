<?php
/**
 * Generic Zaak-related exception.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Exceptions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use RuntimeException;
use Throwable;

/**
 * Generic Zaak-related exception.
 *
 * @since   1.0.0
 */
class ZaakException extends RuntimeException
{
	protected int $statusCode;

	public function __construct(
		string $message = 'An unknown Zaak-related error occurred.',
		int $statusCode = 500,
		?Throwable $previous = null
	) {
		$this->statusCode = $statusCode;
		parent::__construct( $message, $statusCode, $previous );
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	public function toArray(): array
	{
		return array(
			'error' => array(
				'type'    => static::class,
				'message' => $this->getMessage(),
				'status'  => $this->statusCode,
			),
		);
	}
}
