<?php

namespace OWCGravityFormsZGW\Enums;

enum BetrokkeneType: string
{
	case NATUURLIJK_PERSOON = 'natuurlijk_persoon';
	case VESTIGING          = 'vestiging';

	public static function fromString(string $value ): ?self
	{
		return self::tryFrom( $value );
	}
}
