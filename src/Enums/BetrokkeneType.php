<?php

declare(strict_types=1);

/**
 * BetrokkeneType enum.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.3.0
 */

namespace OWCGravityFormsZGW\Enums;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BetrokkeneType enum.
 *
 * @since 1.3.0
 */
enum BetrokkeneType: string
{
	case NATUURLIJK_PERSOON = 'natuurlijk_persoon';
	case VESTIGING          = 'vestiging';

	public static function fromString(string $value ): ?self
	{
		return self::tryFrom( $value );
	}
}
