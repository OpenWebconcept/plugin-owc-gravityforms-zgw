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
		IdentificationValidationControllerTestDouble::$bsn    = '';
		IdentificationValidationControllerTestDouble::$kvk    = '';
		IdentificationValidationControllerTestDouble::$eidas  = false;
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

it(
	'allows the submission when the session provides a BSN',
	function () {
		IdentificationValidationControllerTestDouble::$bsn = '111222333';

		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeTrue();
	}
);

it(
	'allows the submission when an eIDAS session provides a BSN',
	function () {
		IdentificationValidationControllerTestDouble::$bsn   = '111222333';
		IdentificationValidationControllerTestDouble::$eidas = true;

		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeTrue();
	}
);

it(
	'blocks the submission with a specific message when the eIDAS session has no BSN',
	function () {
		IdentificationValidationControllerTestDouble::$eidas = true;

		$controller = new IdentificationValidationControllerTestDouble( true );

		$result = $controller->validate( IdentificationValidationControllerTestDouble::result() );

		expect( $result['is_valid'] )->toBeFalse();
		expect( $controller->validation_message( 'generic', $result['form'] ) )->toContain( 'ingelogd met eIDAS' );
	}
);

/**
 * The DigiD/eHerkenning/eIDAS session classes are not loaded in tests, the session identification is stubbed.
 */
class IdentificationValidationControllerTestDouble extends IdentificationValidationController
{
	/**
	 * Values returned by the mocked rgpost().
	 *
	 * @var array<string, string>
	 */
	public static array $posted = array();

	public static string $bsn = '';

	public static string $kvk = '';

	public static bool $eidas = false;

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

	protected function session_bsn(): string
	{
		return self::$bsn;
	}

	protected function session_kvk(): string
	{
		return self::$kvk;
	}

	protected function has_eidas_session_without_bsn(): bool
	{
		return self::$eidas && '' === self::$bsn;
	}
}
