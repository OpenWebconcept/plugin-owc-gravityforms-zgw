<?php

use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Traits\InformationObject;

beforeEach(
	function () {
		WP_Mock::setUp();

		$this->logger = fake_zgw_logger();

		WP_Mock::userFunction( 'wp_get_environment_type', array( 'return' => 'production' ) );
		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'return' => function ( $thing ) {
					return $thing instanceof FakeWPErrorInformationObject;
				},
			)
		);
		WP_Mock::userFunction(
			'wp_remote_retrieve_response_code',
			array(
				'return' => function ( $response ) {
					return $response['response']['code'] ?? '';
				},
			)
		);
		WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			array(
				'return' => function ( $response ) {
					return $response['body'] ?? '';
				},
			)
		);
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'encodes the response body to base64',
	function () {
		WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'return' => array(
					'response' => array( 'code' => 200 ),
					'body'     => 'file-content',
				),
			)
		);

		$result = ( new InformationObjectTraitUser() )->encode_base64_from_url( 'https://example.com/file.pdf' );

		expect( $result )->toBe( base64_encode( 'file-content' ) );
		expect( $this->logger->errors )->toBe( array() );
	}
);

it(
	'returns an empty string and logs an error when the response body is empty',
	function () {
		WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'return' => array(
					'response' => array( 'code' => 200 ),
					'body'     => '',
				),
			)
		);

		$result = ( new InformationObjectTraitUser() )->encode_base64_from_url( 'https://example.com/file.pdf' );

		expect( $result )->toBe( '' );
		expect( $this->logger->errors )->toBe( array( 'Empty response body while retrieving content from URL: https://example.com/file.pdf' ) );
	}
);

it(
	'returns an empty string and logs an error on a non-2xx response',
	function () {
		WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'return' => array(
					'response' => array( 'code' => 404 ),
					'body'     => 'Not found',
				),
			)
		);

		$result = ( new InformationObjectTraitUser() )->encode_base64_from_url( 'https://example.com/file.pdf' );

		expect( $result )->toBe( '' );
		expect( $this->logger->errors )->toBe( array( 'Unexpected response code while retrieving content from URL: https://example.com/file.pdf' ) );
	}
);

it(
	'returns an empty string and logs an error when the request fails',
	function () {
		WP_Mock::userFunction( 'wp_remote_get', array( 'return' => new FakeWPErrorInformationObject() ) );

		$result = ( new InformationObjectTraitUser() )->encode_base64_from_url( 'https://example.com/file.pdf' );

		expect( $result )->toBe( '' );
		expect( $this->logger->errors )->toBe( array( 'Failed to retrieve content from URL: https://example.com/file.pdf' ) );
	}
);

it(
	'does not prepare informatieobject args when the file content is empty',
	function () {
		WP_Mock::userFunction(
			'wp_remote_get',
			array(
				'return' => array(
					'response' => array( 'code' => 200 ),
					'body'     => '',
				),
			)
		);
		WP_Mock::userFunction( 'wp_remote_head', array( 'times' => 0 ) );

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		expect( $action->prepare( 'https://example.com/file.pdf', 'https://example.com/informatieobjecttypen/1' ) )->toBe( array() );
	}
);

class InformationObjectTraitUser
{
	use InformationObject;
}

class UploadedDocumentsActionTestDouble extends AbstractCreateUploadedDocumentsAction
{
	public function add_uploaded_documents(): ?bool
	{
		return null;
	}

	public function prepare( string $object_url, string $information_object_type ): array
	{
		return $this->prepare_information_object_args( $object_url, $information_object_type );
	}
}

class FakeWPErrorInformationObject
{
	public function get_error_message(): string
	{
		return 'cURL error 28: Operation timed out';
	}
}
