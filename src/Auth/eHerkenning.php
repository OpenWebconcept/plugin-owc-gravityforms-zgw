<?php
/**
 * Retrieve the Chamber of Commerce number (KVK) by integrating with eHerkenning authentication.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Auth;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the Chamber of Commerce number (KVK) by integrating with eHerkenning authentication.
 *
 * @since 1.1.0
 */
class eHerkenning
{
	public static function make(): self
	{
		return new static();
	}

	public function kvk(): string
	{
		if ( ! class_exists( '\OWC\IdpUserData\eHerkenningSession' ) ) {
			return '';
		}

		if ( ! \OWC\IdpUserData\eHerkenningSession::isLoggedIn() || is_null( \OWC\IdpUserData\eHerkenningSession::getUserData() ) ) {
			return '';
		}

		return \OWC\IdpUserData\eHerkenningSession::getUserData()->getKvk();
	}
}
