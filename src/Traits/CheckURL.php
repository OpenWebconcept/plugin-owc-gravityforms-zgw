<?php
/**
 * Check URL trait.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
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
 * @since 1.0.0
 */
trait CheckURL
{
	/**
	 * @since 1.0.0
	 */
	public function check_url($url ): bool
	{
		if ( ! $this->is_valid_url( $url ) ) {
			return false;
		}

		$response = wp_remote_get(
			$url,
			array(
				'sslverify' => owc_gravityforms_zgw_env_is_dev(),
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 1.0.0
	 */
	public function is_valid_url($url ): bool
	{
		$url = filter_var( $url, FILTER_SANITIZE_URL ); // Remove invisible characters such as 'soft hyphens'.

		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
	}
}
