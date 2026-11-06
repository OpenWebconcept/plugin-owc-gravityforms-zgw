<?php

use OWCGravityFormsZGW\GravityForms\Controllers\IdentificationValidationController;

beforeEach(
	function () {
		WP_Mock::setUp();

		if ( ! defined( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX' ) ) {
			define( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX', 'owc-gravityforms-zgw' );
		}

		WP_Mock::userFunction( 'rgpost', array( 'return' => fn ( $key ) => IdentificationValidationControllerTestDouble::$posted[ $key ] ?? '' ) );
		WP_Mock::userFunction( 'esc_html__', array( 'return' => fn ( $text ) => $text ) );

		IdentificationValidationControllerTestDouble::$posted = array();
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'blocks the final submission of a ZGW form without a BSN or KVK',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeFalse();
		expect( $controller->validation_message( 'generic', $result['form'] ) )->toContain( 'Je bent niet (meer) ingelogd' );
	}
);

it(
	'allows the submission when the BSN is overwritten by the form setting',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result( array( 'owc-gravityforms-zgw-form-setting-overwrite-bsn' => '111222333' ) ) );

		expect( $result['is_valid'] )->toBeTrue();
		expect( $controller->validation_message( 'generic', $result['form'] ) )->toBe( 'generic' );
	}
);

it(
	'allows the submission when the KVK is overwritten by the form setting',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result( array( 'owc-gravityforms-zgw-form-setting-overwrite-kvk' => '12345678' ) ) );

		expect( $result['is_valid'] )->toBeTrue();
	}
);

it(
	'does not require identification on forms that are not configured for ZGW',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( false );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeTrue();
	}
);

it(
	'does not require identification when navigating between pages',
	function () {
		IdentificationValidationControllerTestDouble::$posted['gform_target_page_number_1'] = '2';

		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeTrue();
	}
);

it(
	'requires identification on the final submission of a multi-page form',
	function () {
		IdentificationValidationControllerTestDouble::$posted['gform_target_page_number_1'] = '0';

		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeFalse();
	}
);

it(
	'keeps the result invalid when other validation already failed',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( array_merge( IdentificationValidationControllerTestDouble::result( array( 'owc-gravityforms-zgw-form-setting-overwrite-bsn' => '111222333' ) ), array( 'is_valid' => false ) ) );

		expect( $result['is_valid'] )->toBeFalse();
	}
);

it(
	'only replaces the validation message of the form that failed',
	function () {
		$controller = new IdentificationValidationControllerTestDouble( true );

		$controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $controller->validation_message( 'generic', array( 'id' => 2 ) ) )->toBe( 'generic' );
	}
);

/**
 * The DigiD/eHerkenning session classes are not loaded in tests, so only the overwrite settings provide identification.
 */
class IdentificationValidationControllerTestDouble extends IdentificationValidationController
{
	/**
	 * Values returned by the mocked rgpost().
	 *
	 * @var array<string, string>
	 */
	public static array $posted = array();

	public function __construct( private bool $is_zgw ) {}

	public static function result( array $form = array() ): array
	{
		return array(
			'is_valid' => true,
			'form'     => array_merge( array( 'id' => 1 ), $form ),
		);
	}

	protected function is_form_zgw( array $form ): bool
	{
		return $this->is_zgw;
	}
}
