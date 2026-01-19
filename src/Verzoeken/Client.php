<?php
/**
 * Client for ZGW Verzoeken & Documenten APIs.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use Firebase\JWT\JWT;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Singletons\SiteOptionsSingletonCMB2;

/**
 * Client for ZGW Verzoeken & Documenten APIs.
 *
 * @since NEXT
 */
class Client
{
	protected SiteOptionsSingletonCMB2 $settings;

	public function __construct()
	{
		$this->settings = ContainerResolver::make()->get( 'zgw.site_options_cmb2' );
	}

	public function object_types(): array
	{
		return $this->request(
			method: 'GET',
			url: $this->settings->objecttypes_endpoint_url(),
			headers: array(
				'Authorization' => $this->get_verzoeken_auth_header(),
			)
		);
	}

	public function create_object(array $data = array() ): array
	{
		return $this->request(
			method: 'POST',
			url: $this->settings->objects_endpoint_url(),
			headers: array(
				'Authorization' => $this->get_verzoeken_auth_header(),
				'Content-Crs'   => 'EPSG:4326',
				'Accept-Crs'    => 'EPSG:4326',
			),
			body: $data
		);
	}

	public function informationobject_types(): array
	{
		return $this->request(
			method: 'GET',
			url: $this->settings->informationobjecttypes_endpoint_url(),
			headers: array(
				'Authorization' => $this->get_documenten_auth_header(),
				'Content-Type'  => 'application/json',
				'Content-Crs'   => 'EPSG:4326',
				'Accept-Crs'    => 'EPSG:4326',
			)
		);
	}

	/**
	 * Information object creation. Information objects are also known as files.
	 */
	public function create_information_object(array $data = array() ): array
	{
		return $this->request(
			method: 'POST',
			url: $this->settings->informationobject_endpoint(),
			headers: array(
				'Authorization' => $this->get_documenten_auth_header(),
				'Content-Crs'   => 'EPSG:4326',
				'Accept-Crs'    => 'EPSG:4326',
			),
			body: $data
		);
	}

	public function connect_object_to_information_object(array $data = array() ): array
	{
		return $this->request(
			method: 'POST',
			url: $this->settings->object_information_objects_endpoint(),
			headers: array(
				'Authorization' => $this->get_documenten_auth_header(),
				'Content-Crs'   => 'EPSG:4326',
				'Accept-Crs'    => 'EPSG:4326',
			),
			body: $data
		);
	}

	protected function request(string $method, string $url, array $headers = array(), ?array $body = null ): array
	{
		$default_headers = array(
			'Accept' => 'application/json',
		);

		$headers = array_merge( $default_headers, $headers );

		$args = array(
			'headers' => $headers,
		);

		if ( is_array( $body ) && array() !== $body ) {
			$args['body']                    = wp_json_encode( $body );
			$args['headers']['Content-Type'] = $args['headers']['Content-Type'] ?? 'application/json';
		}

		$method   = strtoupper( $method );
		$response = 'POST' === $method
			? wp_remote_post( $url, $args )
			: wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$http_status = wp_remote_retrieve_response_code( $response );

		if ( $http_status < 200 || $http_status >= 300 ) {
			throw new Exception( sprintf( 'ZGW Verzoeken API request failed with status %d', $http_status ) );
		}

		$raw_body = wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Authorization header for Verzoeken API.
	 */
	protected function get_verzoeken_auth_header(): string
	{
		return sprintf( 'Token %s', $this->settings->zgw_verzoeken_token() );
	}

	/**
	 * Authorization header for Documenten API (JWT).
	 */
	protected function get_documenten_auth_header(): string
	{
		return sprintf( 'Bearer %s', $this->generate_token() );
	}

	public function generate_token(): string
	{
		return $this->encode( $this->generate_payload() );
	}

	protected function generate_payload(): array
	{
		$client_id = $this->settings->zgw_catalog_client_id();

		return array(
			'iss'                 => $client_id,
			'iat'                 => time(),
			'client_id'           => $client_id,
			'user_id'             => $client_id,
			'user_representation' => $client_id,
		);
	}

	protected function encode(array $payload ): string
	{
		return JWT::encode(
			$payload,
			$this->settings->zgw_catalog_client_secret(),
			'HS256'
		);
	}

	/**
	 * Default payload for create_object() when no data is supplied.
	 */
	protected function object_test_payload(): array
	{
		return array(
			'type'   => 'https://objecttypen.test.dbp.opengem.nl/api/v2/objecttypes/8f9ba51f-7ef6-4e50-8b68-1e570c2dbad3',
			'record' => array(
				'typeVersion' => 1,
				'startAt'     => '2026-01-19',
				'data'        => array(
					'submission_id' => '1',
					'type'          => 'terugbelnotitie',
					'data'          => array(
						'naam'           => 'Jan Jansen',
						'omschrijving'   => 'Ik heb een vraag over mijn paspoort',
						'telefoonnummer' => '0612345678',
					),
				),
			),
		);
	}
}
