<?php
/**
 * Resolve entries from the DI-container.
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

class DigiD
{
	public static function make(): self
	{
		return new static();
	}

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
