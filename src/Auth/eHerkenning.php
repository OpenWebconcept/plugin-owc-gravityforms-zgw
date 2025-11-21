<?php
/**
 * Retrieve the Chamber of Commerce number (KVK) by integrating with eHerkenning authentication.
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
 * Retrieve the Chamber of Commerce number (KVK) by integrating with eHerkenning authentication.
 */
class eHerkenning
{
	/**
	 * @since NEXT
	 */
	public static function make(): self
	{
		return new static();
	}

	/**
	 * @since NEXT
	 */
	public function kvk(): string
	{
		if ( ! class_exists( '\OWC\IdpUserData\eHerkenningSession' ) ) {
			return '';
		}

		if ( ! \OWC\IdpUserData\eHerkenningSession::isLoggedIn() || is_null( \OWC\IdpUserData\eHerkenningSession::getUserData() ) ) {
			return '';
		}

		$userData = \OWC\IdpUserData\eHerkenningSession::getUserData();

		return ! is_null( $userData ) ? $userData->getKvk() : '';
	}
}
