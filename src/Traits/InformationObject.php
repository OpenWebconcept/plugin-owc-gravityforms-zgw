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

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use OWCGravityFormsZGW\GravityForms\FormUtils;

/**
 * Information object trait.
 *
 * @since 1.0.0
 */
trait InformationObject
{
	public function encode_base64_from_url(string $url ): string
	{
		try {
			$file = file_get_contents( $url, false, $this->stream_context() );
		} catch ( Exception $e ) {
			$file = '';
		}

		return $file ? base64_encode( $file ) : '';
	}

	public function get_content_length(string $url ): string
	{
		$headers        = $this->get_headers( $url );
		$content_length = $headers['Content-Length'] ?? '';

		if ( is_array( $content_length ) && ! empty( $content_length[0] ) ) {
			return $content_length[0];
		}

		return $content_length ?: '';
	}

	public function get_extension(string $url ): string
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

	public function get_content_type(string $url ): string
	{
		$headers      = $this->get_headers( $url );
		$content_type = $headers['content-type'] ?? $headers['Content-Type'] ?? '';

		if ( is_array( $content_type ) && ! empty( $content_type[0] ) ) {
			return $content_type[0];
		}

		return $content_type ?: '';
	}

	protected function get_headers(string $url ): array
	{
		if ( empty( $url ) ) {
			return array();
		}

		try {
			$response = get_headers( $url, true, $this->stream_context() );
		} catch ( Exception $e ) {
			return array();
		}

		return $response ?: array();
	}

	/**
	 * Creates a stream context for SSL connections.
	 *
	 * This method checks the environment type using the `owc_gravityforms_zgw_env_type()` function.
	 * If the environment type does not contain 'dev', it returns null.
	 * Otherwise, it creates and returns a stream context with SSL peer verification disabled.
	 *
	 * @return resource|null The stream context resource or null if not in a 'dev' environment.
	 */
	protected function stream_context(): mixed
	{
		if ( false === owc_gravityforms_zgw_env_is_dev() ) {
			return null;
		}

		return stream_context_create(
			array(
				'ssl' => array(
					'verify_peer'      => false,
					'verify_peer_name' => false,
				),
			)
		);
	}

	/**
	 * @since NEXT
	 */
	protected function format_creation_date_informtion_object(array $form ): string
	{
		$now        = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$wants_time = FormUtils::enrich_creation_date_information_objects( $form );

		if ( $wants_time ) {
			$now = $now->setTime( 12, 0, 0 );

			return $now->format( 'Y-m-d H:i' );
		}

		return $now->format( 'Y-m-d' );
	}
}
