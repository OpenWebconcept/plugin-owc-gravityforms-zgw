<?php
/**
 * Retrieve the social security number (BSN) by integrating with multiple DigiD authentication methods.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Auth;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Retrieve the social security number (BSN) by integrating with multiple DigiD authentication methods.
 */
class DigiD
{
	/**
	 * @since 1.0.0
	 */
	public static function make(): self
	{
		return new static();
	}

	/**
	 * @since 1.0.0
	 */
	public function bsn(): string
	{
		if ($bsn = $this->handle_digid_idp()) {
			return $bsn;
		}

		if ($bsn = $this->handle_digid_saml()) {
			return $bsn;
		}

		return '';
	}

	/**
	 * @since 1.0.0
	 */
	private function handle_digid_idp(): string
	{
		if ( ! class_exists( '\OWC\IdpUserData\DigiDSession' )) {
			return '';
		}

		if ( ! \OWC\IdpUserData\DigiDSession::isLoggedIn() || is_null( \OWC\IdpUserData\DigiDSession::getUserData() )) {
			return '';
		}

		return \OWC\IdpUserData\DigiDSession::getUserData()->getBsn();
	}

	/**
	 * @since 1.0.0
	 */
	private function handle_digid_saml(): string
	{
		if ( ! function_exists( '\\Yard\\DigiD\\Foundation\\Helpers\\resolve' ) ) {
			return '';
		}

		if ( ! function_exists( '\\Yard\\DigiD\\Foundation\\Helpers\\decrypt' ) ) {
			return '';
		}

		$bsn = \Yard\DigiD\Foundation\Helpers\resolve( 'session' )->getSegment( 'digid' )->get( 'bsn' );

		return ! empty( $bsn ) && is_string( $bsn ) ? \Yard\DigiD\Foundation\Helpers\decrypt( $bsn ) : '';
	}
}
