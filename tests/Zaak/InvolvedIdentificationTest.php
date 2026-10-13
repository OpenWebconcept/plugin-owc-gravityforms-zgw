<?php

use OWC\ZGW\Entities\Zaak;
use OWCGravityFormsZGW\Contracts\AbstractCreateZaakAction;

beforeEach(
	function () {
		WP_Mock::setUp();

		WP_Mock::userFunction(
			'rgar',
			array(
				'return' => function ( $entry, $key ) {
					return $entry[ $key ] ?? '';
				},
			)
		);

		$this->action = ( new ReflectionClass( CreateZaakActionTestDouble::class ) )->newInstanceWithoutConstructor();
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'does not let a mapped form field overwrite the BSN derived from the session',
	function () {
		$this->action->set_entry_and_form(
			array(
				'1' => '999999999',
				'2' => 'Jansen',
			),
			array(
				'fields' => array(
					(object) array(
						'id'   => 1,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'inpBsn',
					),
					(object) array(
						'id'   => 2,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'geslachtsnaam',
					),
				),
			)
		);

		$args = $this->action->involved_identification( array( 'betrokkeneIdentificatie' => array( 'inpBsn' => '111222333' ) ) );

		expect( $args['betrokkeneIdentificatie'] )->toBe(
			array(
				'inpBsn'        => '111222333',
				'geslachtsnaam' => 'Jansen',
			)
		);
	}
);

it(
	'ignores a field mapped to inpBsn when a KVK is present in the session',
	function () {
		$this->action->set_entry_and_form(
			array( '1' => '999999999' ),
			array(
				'fields' => array(
					(object) array(
						'id'   => 1,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'inpBsn',
					),
				),
			)
		);

		$args = $this->action->involved_identification( array( 'betrokkeneIdentificatie' => array( 'kvkNummer' => '12345678' ) ) );

		expect( $args['betrokkeneIdentificatie'] )->toBe( array( 'kvkNummer' => '12345678' ) );
	}
);

it(
	'uses a field mapped to inpBsn when there is no BSN or KVK in the session',
	function () {
		$this->action->set_entry_and_form(
			array( '1' => '999999999' ),
			array(
				'fields' => array(
					(object) array(
						'id'   => 1,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'inpBsn',
					),
				),
			)
		);

		$args = $this->action->involved_identification( array() );

		expect( $args['betrokkeneIdentificatie'] )->toBe( array( 'inpBsn' => '999999999' ) );
	}
);

it(
	'does not use a field mapped to inpBsn when the value is not a valid BSN',
	function () {
		$this->action->set_entry_and_form(
			array( '1' => 'test' ),
			array(
				'fields' => array(
					(object) array(
						'id'   => 1,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'inpBsn',
					),
				),
			)
		);

		$args = $this->action->involved_identification( array() );

		expect( $args )->not->toHaveKey( 'betrokkeneIdentificatie' );
	}
);

it(
	'ignores fields mapped to other person identifiers when a BSN is present in the session',
	function () {
		$this->action->set_entry_and_form(
			array(
				'1' => '1234567890',
				'2' => '9876543210',
			),
			array(
				'fields' => array(
					(object) array(
						'id'   => 1,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'anpIdentificatie',
					),
					(object) array(
						'id'   => 2,
						'type' => 'text',
						'mappedFieldValueInvolvedIdentificationZGW' => 'inpA_nummer',
					),
				),
			)
		);

		$args = $this->action->involved_identification( array( 'betrokkeneIdentificatie' => array( 'inpBsn' => '111222333' ) ) );

		expect( $args['betrokkeneIdentificatie'] )->toBe( array( 'inpBsn' => '111222333' ) );
	}
);

class CreateZaakActionTestDouble extends AbstractCreateZaakAction
{
	public function create(): Zaak
	{
		throw new Exception( 'Not implemented' );
	}

	public function set_entry_and_form( array $entry, array $form ): void
	{
		$this->entry = $entry;
		$this->form  = $form;
	}

	public function involved_identification( array $args ): array
	{
		$method = new ReflectionMethod( AbstractCreateZaakAction::class, 'add_involved_identification_to_rol_args' );

		return $method->invoke( $this, $args );
	}
}
