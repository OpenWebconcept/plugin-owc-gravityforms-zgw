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
	/**
	 * Percent-encodes characters outside the printable ASCII range, for example the soft hyphen (U+00AD)
	 * macOS adds to Dutch screenshot file names ("Schermafbeelding"). Requesting the raw URL results in a 404.
	 *
	 * @since 1.18.0
	 */
	public function encode_url( string $url ): string
	{
		return (string) preg_replace_callback(
			'/[^\x21-\x7E]/',
			function ( array $matches ): string {
				return rawurlencode( $matches[0] );
			},
			$url
		);
	}

	/**
	 * Retrieves the content and content type of an uploaded file in a single read.
	 * Reads the file from disk when it is stored locally, which avoids HTTP requests to the site itself.
	 * Falls back to a single HTTP request otherwise.
	 *
	 * @since 1.19.1
	 *
	 * @return array{content: string, type: string, extension: string} Empty array when the file could not be retrieved.
	 *                                                                 The extension is only known for locally stored files.
	 */
	public function retrieve_file( string $url ): array
	{
		$local_file = $this->retrieve_local_file( $url );

		if ( array() !== $local_file ) {
			return $local_file;
		}

		return $this->retrieve_remote_file( $this->encode_url( $url ) );
	}

	/**
	 * @since 1.19.1
	 *
	 * @return array{content: string, type: string, extension: string} Empty array when the file is not stored locally.
	 */
	protected function retrieve_local_file( string $url ): array
	{
		$path = $this->get_local_file_path( $url );

		if ( '' === $path ) {
			return array();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $path );

		if ( false === $content || '' === $content ) {
			return array();
		}

		return array(
			'content'   => $content,
			'type'      => $this->get_local_file_content_type( $path ),
			'extension' => strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ),
		);
	}

	/**
	 * Converts the URL of a Gravity Forms upload to its path on disk.
	 * Only paths inside the Gravity Forms upload root are returned.
	 *
	 * @since 1.19.1
	 */
	protected function get_local_file_path( string $url ): string
	{
		if ( '' === $url || ! class_exists( 'GFFormsModel' ) || ! method_exists( 'GFFormsModel', 'get_physical_file_path' ) ) {
			return '';
		}

		// Uploads can be stored with either the raw or the decoded file name.
		foreach ( array_unique( array( $url, rawurldecode( $url ) ) ) as $candidate ) {
			$physical_path = (string) \GFFormsModel::get_physical_file_path( $candidate );
			$path          = '' !== $physical_path ? realpath( $physical_path ) : false;

			if ( false === $path || ! is_file( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			if ( ! $this->is_inside_upload_root( $path ) ) {
				continue; // Prevents reading files outside the Gravity Forms upload root.
			}

			return $path;
		}

		return '';
	}

	/**
	 * @since 1.19.1
	 */
	protected function is_inside_upload_root( string $path ): bool
	{
		if ( ! method_exists( 'GFFormsModel', 'get_upload_root' ) ) {
			return false;
		}

		$upload_root = (string) \GFFormsModel::get_upload_root();
		$upload_root = '' !== $upload_root ? realpath( $upload_root ) : false;

		if ( false === $upload_root ) {
			return false;
		}

		return 0 === strpos( $path, rtrim( $upload_root, '/\\' ) . DIRECTORY_SEPARATOR );
	}

	/**
	 * @since 1.19.1
	 */
	protected function get_local_file_content_type( string $path ): string
	{
		$type = (string) ( wp_check_filetype( $path )['type'] ?? '' );

		if ( '' === $type && function_exists( 'mime_content_type' ) ) {
			$type = (string) mime_content_type( $path );
		}

		return $type;
	}

	/**
	 * @since 1.19.1
	 *
	 * @return array{content: string, type: string, extension: string} Empty array when the request failed.
	 */
	protected function retrieve_remote_file( string $url ): array
	{
		$response = wp_safe_remote_get(
			$url,
			array(
				'sslverify' => owc_gravityforms_zgw_env_is_prod(),
			)
		);

		if ( is_wp_error( $response ) ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Failed to retrieve content from URL: ' . $url, array( 'error' => $response->get_error_message() ) );

			return array();
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error(
				'Unexpected response code while retrieving content from URL: ' . $url,
				array( 'status_code' => $status_code )
			);

			return array();
		}

		$body = wp_remote_retrieve_body( $response );

		if ( '' === $body ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Empty response body while retrieving content from URL: ' . $url );

			return array();
		}

		return array(
			'content'   => $body,
			'type'      => $this->get_response_content_type( $response ),
			'extension' => '', // Derived from the content type, the URL of a remote file is not reliable.
		);
	}

	/**
	 * The header is returned as an array when the response contains multiple content-type headers.
	 *
	 * @since 1.19.1
	 */
	protected function get_response_content_type( array $response ): string
	{
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		if ( is_array( $content_type ) ) {
			$content_type = reset( $content_type );
		}

		return is_string( $content_type ) ? $content_type : '';
	}

	/**
	 * @since 1.19.1
	 */
	public function get_extension_from_content_type( string $content_type ): string
	{
		$type     = strtolower( trim( explode( ';', $content_type )[0] ) );
		$mime_map = array(
			'application/msword'                      => 'doc',
			'application/json'                        => 'json',
			'application/pdf'                         => 'pdf',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/vnd.ms-excel'                => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/vnd.oasis.opendocument.text' => 'odt',
			'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
			'application/vnd.oasis.opendocument.presentation' => 'odp',
			'application/xml'                         => 'xml',
			'image/jpeg'                              => 'jpg',
			'image/png'                               => 'png',
			'text/plain'                              => 'txt',
			'text/csv'                                => 'csv',
			'text/html'                               => 'html',
		);

		return $mime_map[ $type ] ?? '';
	}

	/**
	 * @deprecated 1.19.1 Use retrieve_file() instead, which retrieves the content and content type in a single read.
	 */
	public function encode_base64_from_url( string $url ): string
	{
		$file = $this->retrieve_remote_file( $url );

		return array() !== $file ? base64_encode( $file['content'] ) : '';
	}

	/**
	 * @deprecated 1.19.1 Use the length of the content returned by retrieve_file() instead.
	 */
	public function get_content_length( string $url ): string
	{
		$content_length = $this->get_headers( $url )['content-length'] ?? '';

		if ( is_array( $content_length ) ) {
			$content_length = reset( $content_length );
		}

		return is_string( $content_length ) ? $content_length : '';
	}

	/**
	 * @deprecated 1.19.1 Use get_extension_from_content_type() with the type returned by retrieve_file() instead.
	 */
	public function get_extension( string $url ): string
	{
		return $this->get_extension_from_content_type( $this->get_content_type( $url ) );
	}

	/**
	 * @deprecated 1.19.1 Use the type returned by retrieve_file() instead.
	 */
	public function get_content_type( string $url ): string
	{
		$content_type = $this->get_headers( $url )['content-type'] ?? '';

		if ( is_array( $content_type ) ) {
			$content_type = reset( $content_type );
		}

		return is_string( $content_type ) ? $content_type : '';
	}

	/**
	 * @deprecated 1.19.1
	 */
	protected function get_headers( string $url ): array
	{
		if ( empty( $url ) ) {
			return array();
		}

		$response = wp_safe_remote_head(
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
