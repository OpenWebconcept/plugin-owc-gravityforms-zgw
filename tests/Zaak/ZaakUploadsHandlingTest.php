<?php

use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Contracts\AbstractCreateUploadedDocumentsAction;
use OWCGravityFormsZGW\Exceptions\ZaakUploadException;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakController;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakUploadPDFController;
use OWCGravityFormsZGW\GravityForms\Controllers\ZaakUploadsController;
use OWCGravityFormsZGW\LoggerZGW;

beforeEach(
	function () {
		WP_Mock::setUp();

		$this->uploads = Mockery::mock( ZaakUploadsController::class );
		$this->uploads->shouldReceive( 'set_form_data' );

		$this->pdf = Mockery::mock( ZaakUploadPDFController::class );
		$this->pdf->shouldReceive( 'set_form_data' );

		$this->controller = ( new ReflectionClass( ZaakUploadsHandlingTestDouble::class ) )->newInstanceWithoutConstructor();
		$this->controller->configure( $this->uploads, $this->pdf, Mockery::mock( LoggerZGW::class )->shouldIgnoreMissing() );
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
		Mockery::close();
	}
);

function zaak_with_identification( $identification ): Zaak
{
	$zaak = ( new ReflectionClass( Zaak::class ) )->newInstanceWithoutConstructor();
	$zaak->setValue( 'identificatie', $identification );

	return $zaak;
}

it(
	'does not add the submission PDF when the file uploads fail',
	function () {
		$this->uploads->shouldReceive( 'handle' )->once()->andThrow( new ZaakUploadException( 'Error processing uploads: timeout' ) );
		$this->pdf->shouldNotReceive( 'handle' );

		expect( fn() => $this->controller->uploads( zaak_with_identification( 'ZAAK-1' ) ) )
			->toThrow( ZaakUploadException::class, 'Error processing uploads: timeout' );
	}
);

it(
	'fails when the submission PDF cannot be added because the zaak has no ID',
	function ( $identification ) {
		$this->uploads->shouldReceive( 'handle' )->once();
		$this->pdf->shouldNotReceive( 'handle' );

		expect( fn() => $this->controller->uploads( zaak_with_identification( $identification ) ) )
			->toThrow( ZaakUploadException::class, 'Submission PDF could not be added: zaak ID is not available.' );
	}
)->with(
	array(
		'missing' => null,
		'empty'   => '',
	)
);

it(
	'keeps the original exception when the uploads fail',
	function () {
		$original = new RuntimeException( 'cURL error 28: Operation timed out' );

		$this->uploads->shouldReceive( 'handle' )->once()->andThrow( $original );

		try {
			$this->controller->uploads( zaak_with_identification( 'ZAAK-1' ) );
		} catch ( ZaakUploadException $e ) {
			expect( $e->getPrevious() )->toBe( $original );

			return;
		}

		$this->fail( 'Expected a ZaakUploadException.' );
	}
);

it(
	'mentions the failed file once in the error of an upload that could not be added',
	function ( ?Throwable $thrown, string $expected_message ) {
		fake_zgw_logger();

		$action        = ( new ReflectionClass( StubUploadedDocumentsAction::class ) )->newInstanceWithoutConstructor();
		$action->throw = $thrown;

		$controller = ( new ReflectionClass( ZaakUploadsControllerTestDouble::class ) )->newInstanceWithoutConstructor();
		$controller->configure( $action, Mockery::mock( LoggerZGW::class )->shouldIgnoreMissing() );

		expect( fn() => $controller->handle( zaak_with_identification( 'ZAAK-1' ), array() ) )
			->toThrow( ZaakUploadException::class, $expected_message );
	}
)->with(
	array(
		'failed upload' => array( null, 'Error processing uploads: An upload could not be added to zaak ZAAK-1. file.pdf: File could not be retrieved.' ),
		'API exception' => array( new RuntimeException( 'cURL error 28: Operation timed out' ), 'Error processing uploads: file.pdf: cURL error 28: Operation timed out' ),
	)
);

it(
	'adds the file uploads and the submission PDF',
	function () {
		$this->uploads->shouldReceive( 'handle' )->once();
		$this->pdf->shouldReceive( 'handle' )->once();

		$this->controller->uploads( zaak_with_identification( 'ZAAK-1' ) );
	}
);

class ZaakUploadsHandlingTestDouble extends ZaakController
{
	public function configure( ZaakUploadsController $uploads, ZaakUploadPDFController $pdf, LoggerZGW $logger ): void
	{
		$reflection = new ReflectionClass( ZaakController::class );

		$reflection->getProperty( 'uploads_controller' )->setValue( $this, $uploads );
		$reflection->getProperty( 'upload_pdf_controller' )->setValue( $this, $pdf );

		$this->logger = $logger;
		$this->entry  = array();
		$this->form   = array();
	}

	public function uploads( Zaak $zaak ): void
	{
		$this->handle_uploads( $zaak, array() );
	}
}

class StubUploadedDocumentsAction extends AbstractCreateUploadedDocumentsAction
{
	public ?Throwable $throw = null;

	public function add_uploaded_documents(): ?bool
	{
		$this->set_upload_error( 'https://example.com/uploads/file.pdf', $this->throw ? $this->throw->getMessage() : 'File could not be retrieved.' );

		if ( $this->throw ) {
			throw $this->throw;
		}

		return false;
	}
}

class ZaakUploadsControllerTestDouble extends ZaakUploadsController
{
	private AbstractCreateUploadedDocumentsAction $action;

	public function configure( AbstractCreateUploadedDocumentsAction $action, LoggerZGW $logger ): void
	{
		$this->action = $action;
		$this->logger = $logger;
		$this->entry  = array();
		$this->form   = array();
	}

	protected function create_uploaded_documents_action( Zaak $zaak, array $supplier_config ): AbstractCreateUploadedDocumentsAction
	{
		return $this->action;
	}
}
