<?php

use OWCGravityFormsZGW\Contracts\AbstractCreateSubmissionPDFAction;

beforeEach(
	function () {
		WP_Mock::setUp();

		$this->logger = fake_zgw_logger();

		if ( ! defined( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX' ) ) {
			define( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX', 'owc-gravityforms-zgw' );
		}

		$this->action = ( new ReflectionClass( SubmissionPDFActionTestDouble::class ) )->newInstanceWithoutConstructor();
		$this->action->configure(
			array( 'owc-gravityforms-zgw-form-setting-openzaak-information-object-type' => 'https://example.com/informatieobjecttypen/1' ),
			'openzaak'
		);
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'does not prepare PDF args and logs an error when the PDF is empty',
	function () {
		$pdf_path = tempnam( sys_get_temp_dir(), 'owc-zgw-pdf-' );

		expect( $this->action->prepare_args_pdf( 'Aanvraag', $pdf_path ) )->toBe( array() );
		expect( $this->logger->errors )->toBe( array( 'Submission PDF is empty or could not be read: ' . $pdf_path ) );

		unlink( $pdf_path );
	}
);

it(
	'does not prepare PDF args and logs an error when the PDF is not readable',
	function () {
		$pdf_path = sys_get_temp_dir() . '/owc-zgw-non-existing.pdf';

		expect( $this->action->prepare_args_pdf( 'Aanvraag', $pdf_path ) )->toBe( array() );
		expect( $this->logger->errors )->toBe( array( 'Submission PDF is not readable: ' . $pdf_path ) );
	}
);

class SubmissionPDFActionTestDouble extends AbstractCreateSubmissionPDFAction
{
	public function add_submission_pdf(): ?OWC\ZGW\Entities\Zaakinformatieobject
	{
		return null;
	}

	public function configure( array $form, string $supplier_name ): void
	{
		$this->form          = $form;
		$this->supplier_name = $supplier_name;
	}
}
