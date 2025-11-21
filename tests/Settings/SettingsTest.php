<?php

namespace OWCGravityFormsZGW\Tests\Settings;

use OWCGravityFormsZGW\Settings\Settings;

beforeAll(
	function () {
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', __DIR__ . '/../../../' );
		}
	}
);

it(
	'retrieves the correct value for an existing key',
	function () {
		$settings = Settings::make( 'owc_zgw_settings' );

		expect( $settings->get( 'api_url' ) )->toBe( 'https://api.example.com' );
		expect( $settings->get( 'api_key' ) )->toBe( 'secret-key' );
	}
);

it(
	'returns an empty string for a non-existing key',
	function () {
		$settings = Settings::make( 'owc_zgw_settings' );

		expect( $settings->get( 'non_existing_key' ) )->toBe( '' );
	}
);
