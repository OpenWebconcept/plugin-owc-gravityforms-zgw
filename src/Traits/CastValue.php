<?php

declare(strict_types=1);

/**
 * Cast value trait.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check URL trait.
 *
 * @since NEXT
 */
trait CastValue
{
	/**
	 * @since NEXT
	 */
	public function cast_value(string $type, $value ): int|string
	{
		switch ( $type ) {
			case 'int':
				return (int) $value;

			case 'string':
			default:
				return (string) $value;
		}
	}
}
