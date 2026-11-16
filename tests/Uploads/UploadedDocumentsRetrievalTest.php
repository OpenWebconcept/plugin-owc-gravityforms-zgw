<?php

use OWC\ZGW\Entities\Enkelvoudiginformatieobject;
use OWC\ZGW\Entities\Zaakinformatieobject;
use OWCGravityFormsZGW\Actions\CreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Traits\InformationObject;

beforeEach(
	function () {
		WP_Mock::setUp();

		$this->logger = fake_zgw_logger();

		$this->uploads_dir = sys_get_temp_dir() . '/owc-zgw-uploads-' . uniqid();
		mkdir( $this->uploads_dir . '/gravity_forms', 0777, true );
		file_put_contents( $this->uploads_dir . '/gravity_forms/file.pdf', 'local-content' );

		$this->outside_file = sys_get_temp_dir() . '/owc-zgw-outside-' . uniqid() . '.pdf';
		file_put_contents( $this->outside_file, 'secret' );

		// Inside the uploads directory, but outside the Gravity Forms upload root.
		$this->uploads_file = $this->uploads_dir . '/other.pdf';
		file_put_contents( $this->uploads_file, 'other' );

		GFFormsModel::$paths       = array();
		GFFormsModel::$upload_root = $this->uploads_dir . '/gravity_forms/';

		WP_Mock::userFunction( 'wp_upload_dir', array( 'return' => array( 'basedir' => $this->uploads_dir ) ) );
		WP_Mock::userFunction( 'wp_check_filetype', array( 'return' => array( 'type' => 'application/pdf' ) ) );
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();

		@unlink( $this->uploads_dir . '/gravity_forms/file.pdf' );
		@unlink( $this->uploads_file );
		@rmdir( $this->uploads_dir . '/gravity_forms' );
		@rmdir( $this->uploads_dir );
		@unlink( $this->outside_file );

		GFFormsModel::$paths = array();
	}
);

it(
	'reads a locally stored upload from disk without HTTP requests',
	function () {
		GFFormsModel::$paths['https://example.com/uploads/gravity_forms/file.pdf'] = $this->uploads_dir . '/gravity_forms/file.pdf';

		WP_Mock::userFunction( 'wp_safe_remote_get', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'wp_safe_remote_head', array( 'times' => 0 ) );

		$result = ( new LocalInformationObjectTraitUser() )->retrieve_file( 'https://example.com/uploads/gravity_forms/file.pdf' );

		expect( $result )->toBe(
			array(
				'content'   => 'local-content',
				'type'      => 'application/pdf',
				'extension' => 'pdf',
			)
		);
	}
);

it(
	'does not read files outside the Gravity Forms upload root and falls back to a request',
	function ( string $property ) {
		GFFormsModel::$paths['https://example.com/uploads/gravity_forms/file.pdf'] = $this->{$property};

		WP_Mock::userFunction(
			'wp_safe_remote_get',
			array(
				'times'  => 1,
				'return' => new FakeWPErrorRetrieval(),
			)
		);
		WP_Mock::userFunction( 'is_wp_error', array( 'return' => true ) );

		$result = ( new LocalInformationObjectTraitUser() )->retrieve_file( 'https://example.com/uploads/gravity_forms/file.pdf' );

		expect( $result )->toBe( array() );
	}
)->with(
	array(
		'outside the uploads directory' => 'outside_file',
		'inside the uploads directory'  => 'uploads_file',
	)
);

it(
	'stops at the first failing upload and rethrows the original exception',
	function () {
		$action = ( new ReflectionClass( PartiallyFailingUploadedDocumentsAction::class ) )->newInstanceWithoutConstructor();

		expect( fn() => $action->add_uploaded_documents() )->toThrow( RuntimeException::class, 'cURL error 28: Operation timed out' );
		expect( $action->created )->toBe( array( 'https://example.com/1.pdf' ) );
		expect( $action->get_upload_error_file() )->toBe( '2.pdf' );
		expect( $action->get_upload_error() )->toBe( '2.pdf: cURL error 28: Operation timed out' );
		expect( $this->logger->errors )->toBe( array( 'Failed to add upload to zaak: 2.pdf: cURL error 28: Operation timed out' ) );
	}
);

it(
	'fails when the file of an upload could not be retrieved',
	function () {
		$action                = ( new ReflectionClass( PartiallyFailingUploadedDocumentsAction::class ) )->newInstanceWithoutConstructor();
		$action->unretrievable = 'https://example.com/1.pdf';

		expect( $action->add_uploaded_documents() )->toBeFalse();
		expect( $action->created )->toBe( array() );
		expect( $action->get_upload_error() )->toBe( '1.pdf: File could not be retrieved.' );
	}
);

it(
	'fails with a specific error when the upload has no document type',
	function () {
		$action                = ( new ReflectionClass( PartiallyFailingUploadedDocumentsAction::class ) )->newInstanceWithoutConstructor();
		$action->without_type  = 'https://example.com/1.pdf';
		$action->unretrievable = 'https://example.com/1.pdf';

		expect( $action->add_uploaded_documents() )->toBeFalse();
		expect( $action->created )->toBe( array() );
		expect( $action->get_upload_error() )->toBe( '1.pdf: No document type (informatieobjecttype) is configured.' );
	}
);

it(
	'succeeds when all uploads are added',
	function () {
		$action          = ( new ReflectionClass( PartiallyFailingUploadedDocumentsAction::class ) )->newInstanceWithoutConstructor();
		$action->failing = '';

		expect( $action->add_uploaded_documents() )->toBeTrue();
		expect( $action->created )->toBe( array( 'https://example.com/1.pdf', 'https://example.com/2.pdf', 'https://example.com/3.pdf' ) );
		expect( $action->get_upload_error() )->toBe( '' );
	}
);

if ( ! class_exists( 'GFFormsModel' ) ) {
	class GFFormsModel // phpcs:ignore -- Minimal stand-in for Gravity Forms, which is not loaded in the tests.
	{
		public static array $paths = array();

		public static ?string $upload_root = null;

		public static function get_upload_root()
		{
			return self::$upload_root;
		}

		public static function get_physical_file_path( $url, $entry_id = null ): string
		{
			return self::$paths[ $url ] ?? '';
		}
	}
}

class LocalInformationObjectTraitUser
{
	use InformationObject;
}

class PartiallyFailingUploadedDocumentsAction extends CreateUploadedDocumentsAction
{
	public array $created        = array();
	public string $unretrievable = '';
	public string $failing       = 'https://example.com/2.pdf';
	public string $without_type  = '';

	protected function get_mapped_required_information_object_creation_args(): array
	{
		return array(
			'informatieobject' => array_map(
				fn( $url ) => array(
					'type'        => $url === $this->without_type ? '' : 'https://example.com/informatieobjecttypen/1',
					'url'         => $url,
					'description' => '',
				),
				array( 'https://example.com/1.pdf', 'https://example.com/2.pdf', 'https://example.com/3.pdf' )
			),
		);
	}

	protected function prepare_information_object_args( string $object_url, string $information_object_type, string $object_description = '', string $custom_file_name = '' ): array
	{
		return $object_url === $this->unretrievable ? array() : array( 'url' => $object_url );
	}

	protected function create_information_object( array $args ): ?Enkelvoudiginformatieobject
	{
		if ( $this->failing === $args['url'] ) {
			throw new RuntimeException( 'cURL error 28: Operation timed out' );
		}

		$this->created[] = $args['url'];

		return ( new ReflectionClass( Enkelvoudiginformatieobject::class ) )->newInstanceWithoutConstructor();
	}

	protected function connect_zaak_to_information_object( ?Enkelvoudiginformatieobject $information_object ): ?Zaakinformatieobject
	{
		return ( new ReflectionClass( Zaakinformatieobject::class ) )->newInstanceWithoutConstructor();
	}
}

class FakeWPErrorRetrieval
{
	public function get_error_message(): string
	{
		return 'Request failed';
	}
}
