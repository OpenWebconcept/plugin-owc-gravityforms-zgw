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
	'names a single upload after its document type',
	function () {
		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		$result = $action->document_type_file_names(
			array(
				array( 'type' => 'https://example.com/informatieobjecttypen/1' ),
			)
		);

		expect( array_column( $result, 'file_name' ) )->toBe( array( 'Bijlage' ) );
	}
);

it(
	'numbers uploads that share a document type across the whole form',
	function () {
		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		// Uploads from two different fields, both linked to "Bijlage", and one linked to "Foto".
		$result = $action->document_type_file_names(
			array(
				array( 'type' => 'https://example.com/informatieobjecttypen/1' ),
				array( 'type' => 'https://example.com/informatieobjecttypen/2' ),
				array( 'type' => 'https://example.com/informatieobjecttypen/1' ),
				array( 'type' => 'https://example.com/informatieobjecttypen/1' ),
			)
		);

		expect( array_column( $result, 'file_name' ) )->toBe( array( 'Bijlage_1', 'Foto', 'Bijlage_2', 'Bijlage_3' ) );
	}
);

it(
	'keeps the default file name when the document type name cannot be retrieved',
	function () {
		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();

		$result = $action->document_type_file_names(
			array(
				array( 'type' => 'https://example.com/informatieobjecttypen/unknown' ),
			)
		);

		expect( $result )->toBe( array( array( 'type' => 'https://example.com/informatieobjecttypen/unknown' ) ) );
		expect( $action->file_name( 'https://example.com/uploads/original.pdf', '' ) )->toMatch( '/^[a-f0-9]+_original$/' );
	}
);

it(
	'retrieves the document type name from the cached document types',
	function () {
		WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'supplier-form-settings-information-object-type' ),
				'return' => array(
					array(
						'value'        => 'https://example.com/informatieobjecttypen/1',
						'omschrijving' => 'Bijlage',
					),
				),
			)
		);

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();
		$action->set_supplier_name( 'Supplier' );

		expect( $action->cached_document_type_name( 'https://example.com/informatieobjecttypen/1' ) )->toBe( 'Bijlage' );
		expect( $action->cached_document_type_name( 'https://example.com/informatieobjecttypen/2' ) )->toBe( '' );
	}
);

it(
	'returns an empty cached document type name when the cache is empty',
	function () {
		WP_Mock::userFunction( 'get_transient', array( 'return' => false ) );

		$action = ( new ReflectionClass( UploadedDocumentsActionTestDouble::class ) )->newInstanceWithoutConstructor();
		$action->set_supplier_name( 'Supplier' );

		expect( $action->cached_document_type_name( 'https://example.com/informatieobjecttypen/1' ) )->toBe( '' );
	}
);

class InformationObjectTraitUser
{
	use InformationObject;
}

class UploadedDocumentsActionTestDouble extends AbstractCreateUploadedDocumentsAction
{
	public bool $use_document_type_file_names = false;

	public function add_uploaded_documents(): ?bool
	{
		return null;
	}

	public function prepare( string $object_url, string $information_object_type ): array
	{
		return $this->prepare_information_object_args( $object_url, $information_object_type );
	}

	public function set_form( array $form ): void
	{
		$this->form = $form;
	}

	public function file_name( string $object_url, string $custom_file_name ): string
	{
		return $this->create_file_name( $object_url, $custom_file_name );
	}

	public function set_supplier_name( string $supplier_name ): void
	{
		$this->supplier_name = $supplier_name;
	}

	public function cached_document_type_name( string $document_type_url ): string
	{
		return $this->get_cached_document_type_name( $document_type_url );
	}

	public function document_type_file_names( array $objects ): array
	{
		return $this->add_document_type_file_names( $objects );
	}

	protected function use_document_type_file_names(): bool
	{
		return $this->use_document_type_file_names;
	}

	protected function get_document_type_name( string $document_type_url ): string
	{
		$names = array(
			'https://example.com/informatieobjecttypen/1' => 'Bijlage',
			'https://example.com/informatieobjecttypen/2' => 'Foto',
		);

		return $names[ $document_type_url ] ?? '';
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
