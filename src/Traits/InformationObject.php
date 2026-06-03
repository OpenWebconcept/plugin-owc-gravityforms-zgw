<?php

declare(strict_types=1);

/**
 * Informtion object trait.
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

use OWCGravityFormsZGW\ContainerResolver;
use Traversable;

/**
 * Information object trait.
 *
 * @since 1.0.0
 */
trait InformationObject
{
	public function encode_base64_from_url( string $url ): string
	{
		$response = wp_remote_get(
			$url,
			array(
				'sslverify' => owc_gravityforms_zgw_env_is_prod(),
			)
		);

		if ( is_wp_error( $response ) ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Failed to retrieve content from URL: ' . $url, array( 'error' => $response->get_error_message() ) );

			return '';
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error(
				'Unexpected response code while retrieving content from URL: ' . $url,
				array( 'status_code' => $status_code )
			);

			return '';
		}

		$body = wp_remote_retrieve_body( $response );

		return '' !== $body ? base64_encode( $body ) : '';
	}

	public function get_content_length( string $url ): string
	{
		$headers        = $this->get_headers( $url );
		$content_length = $headers['content-length'] ?? '';

		if ( is_array( $content_length ) && ! empty( $content_length[0] ) ) {
			return $content_length[0];
		}

		return $content_length ?: '';
	}

	public function get_extension( string $url ): string
	{
		$type     = $this->get_content_type( $url );
		$mime_map = array(
			'application/msword'       => 'doc',
			'application/json'         => 'json',
			'application/pdf'          => 'pdf',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/xml'          => 'xml',
			'image/jpeg'               => 'jpg',
			'image/png'                => 'png',
			'text/plain'               => 'txt',
			'text/csv'                 => 'csv',
			'text/html'                => 'html',
		);

		return $mime_map[ $type ] ?? '';
	}

	public function get_content_type( string $url ): string
	{
		$headers      = $this->get_headers( $url );
		$content_type = $headers['content-type'] ?? '';

		if ( is_array( $content_type ) && ! empty( $content_type[0] ) ) {
			return $content_type[0];
		}

		return $content_type ?: '';
	}

	protected function get_headers( string $url ): array
	{
		if ( empty( $url ) ) {
			return array();
		}

		$response = wp_remote_head(
			$url,
			array(
				'sslverify' => owc_gravityforms_zgw_env_is_prod(),
			)
		);

		if ( is_wp_error( $response ) ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Failed to retrieve headers from URL: ' . $url, array( 'error' => $response->get_error_message() ) );

			return array();
		}

		$headers = wp_remote_retrieve_headers( $response );

		if ( $headers instanceof Traversable ) {
			$headers = iterator_to_array( $headers );
		}

		if ( ! is_array( $headers ) ) {
			return array();
		}

		return array_change_key_case( $headers, CASE_LOWER );
	}
}
