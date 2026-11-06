<?php

use OWC\ZGW\Contracts\Client;
use OWCGravityFormsZGW\Actions\CreateZaakAction;

beforeEach(
	function () {
		WP_Mock::setUp();

		if ( ! defined( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX' ) ) {
			define( 'OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX', 'owc-gravityforms-zgw' );
		}

		// No transaction post, so no BSN or KVK was stored at submission.
		WP_Mock::userFunction( 'get_posts', array( 'return' => array() ) );
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'does not create a zaak when there is no BSN or KVK',
	function () {
		$client = $this->createMock( Client::class );
		$client->expects( $this->never() )->method( $this->anything() ); // Endpoints such as zaken() are resolved via __call().

		$action = ( new ReflectionClass( CreateZaakAction::class ) )->newInstanceWithoutConstructor();

		foreach ( array(
			'entry'  => array( 'id' => 1 ),
			'form'   => array( 'fields' => array() ),
			'client' => $client,
		) as $property => $value ) {
			( new ReflectionProperty( $action, $property ) )->setValue( $action, $value );
		}

		expect( fn () => $action->create() )->toThrow( Exception::class, 'This session appears to have no BSN or KVK' );
	}
);
