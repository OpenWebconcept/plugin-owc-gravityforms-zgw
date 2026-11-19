<?php

declare(strict_types=1);

/**
 * Retrieve the social security number (BSN) from an eIDAS authentication session.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Auth;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the social security number (BSN) from an eIDAS authentication session.
 *
 * Users logging in with eIDAS are citizens of other EU countries. A BSN is only available when the
 * broker was able to resolve the eIDAS identity to a Dutch citizen service number.
 *
 * @since NEXT
 */
class eIDAS
{
	public static function make(): self
	{
		return new static();
	}

	public function is_logged_in(): bool
	{
		if ( ! class_exists( '\OWC\IdpUserData\eIDASSession' ) ) {
			return false;
		}

		return \OWC\IdpUserData\eIDASSession::isLoggedIn() && ! is_null( \OWC\IdpUserData\eIDASSession::getUserData() );
	}

	/**
	 * Returns an empty string when the user is not logged in with eIDAS or no BSN is available.
	 */
	public function bsn(): string
	{
		if ( ! $this->is_logged_in() ) {
			return '';
		}

		$bsn = trim( (string) \OWC\IdpUserData\eIDASSession::getUserData()->getBsn() );

		if ( '' === $bsn || ! ctype_digit( $bsn ) ) {
			return '';
		}

		// Leading zeros may be stripped by the broker.
		return str_pad( $bsn, 9, '0', STR_PAD_LEFT );
	}
}
