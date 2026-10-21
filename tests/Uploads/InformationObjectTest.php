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
			'wp_safe_remote_get',
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
			'wp_safe_remote_get',
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
			'wp_safe_remote_get',
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
		WP_Mock::userFunction( 'wp_safe_remote_get', array( 'return' => new FakeWPErrorInformationObject() ) );

		$result = ( new InformationObjectTraitUser() )->encode_base64_from_url( 'https://example.com/file.pdf' );

		expect( $result )->toBe( '' );
		expect( $this->logger->errors )->toBe( array( 'Failed to retrieve content from URL: https://example.com/file.pdf' ) );
	}
);

it(
	'does not prepare informatieobject args when the file content is empty',
	function () {
		WP_Mock::userFunction(
			'wp_safe_remote_get',
			array(
				'return' => array(
					'response' => array( 'code' => 200 ),
					'body'     => '',
				),
			)
		);
		WP_Mock::userFunction( 'wp_safe_remote_head', array( 'times' => 0 ) );

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		expect( $action->prepare( 'https://example.com/file.pdf', 'https://example.com/informatieobjecttypen/1' ) )->toBe( array() );
	}
);

it(
	'ignores informatieobject mappings on fields that are not file uploads',
	function () {
		WP_Mock::userFunction( 'rgar', array( 'times' => 0 ) );

		$field = new FakeGFFieldInformationObject( 'text' );

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();
		$action->set_form( array( 'fields' => array( $field ) ) );

		expect( $action->mapped_args() )->toBe( array( 'informatieobject' => array() ) );
	}
);

it(
	'percent-encodes non-ASCII characters such as a soft hyphen in the URL',
	function () {
		$url = "https://example.com/app/uploads/gravity_forms/2026/10/Scherm\u{00AD}afbeelding 11.51.05.png";

		expect( ( new InformationObjectTraitUser() )->encode_url( $url ) )
			->toBe( 'https://example.com/app/uploads/gravity_forms/2026/10/Scherm%C2%ADafbeelding%2011.51.05.png' );
	}
);

it(
	'leaves an ASCII URL untouched',
	function () {
		$url = 'https://example.com/file.pdf?a=1&b=%20';

		expect( ( new InformationObjectTraitUser() )->encode_url( $url ) )->toBe( $url );
	}
);

it(
	'requests the encoded URL when preparing informatieobject args',
	function () {
		WP_Mock::userFunction(
			'wp_safe_remote_get',
			array(
				'times'  => 1,
				'args'   => array( 'https://example.com/Scherm%C2%ADafbeelding.png', WP_Mock\Functions::type( 'array' ) ),
				'return' => array(
					'response' => array( 'code' => 404 ),
					'body'     => '',
				),
			)
		);

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		expect( $action->prepare( "https://example.com/Scherm\u{00AD}afbeelding.png", 'https://example.com/informatieobjecttypen/1' ) )->toBe( array() );
	}
);

it(
	'removes soft hyphens from the file name',
	function () {
		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		expect( $action->file_name( "https://example.com/Scherm\u{00AD}afbeelding.png" ) )->toEndWith( '_Schermafbeelding' );
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

	public function file_name( string $object_url ): string
	{
		return $this->create_file_name( $object_url );
	}

	public function set_form( array $form ): void
	{
		$this->form = $form;
	}

	public function mapped_args(): array
	{
		return $this->get_mapped_required_information_object_creation_args();
	}
}

class FakeGFFieldInformationObject
{
	public $id                              = 1;
	public $mappedFieldValueZGW             = 'informatieobject';
	public $mappedFieldDocumentTypeValueZGW = 'https://example.com/informatieobjecttypen/1';

	public function __construct( private string $input_type )
	{
	}

	public function get_input_type(): string
	{
		return $this->input_type;
	}
}

class FakeWPErrorInformationObject
{
	public function get_error_message(): string
	{
		return 'cURL error 28: Operation timed out';
	}
}
