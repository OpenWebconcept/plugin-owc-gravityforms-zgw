<?php

use OWCGravityFormsZGW\GravityForms\ZaakeigenschappenMigrator;

const OLD_LEEFTIJD = 'https://catalogi.example.com/eigenschappen/old-leeftijd';
const OLD_KLEUR    = 'https://catalogi.example.com/eigenschappen/old-kleur';
const NEW_LEEFTIJD = 'https://catalogi.example.com/eigenschappen/new-leeftijd';

function migrate_mapping( string $mapped ): string
{
	$form = ZaakeigenschappenMigrator::remap_fields(
		array( 'fields' => array( array( 'mappedFieldValueZGW' => $mapped ) ) ),
		array(
			OLD_LEEFTIJD => 'leeftijd',
			OLD_KLEUR    => 'kleur',
		),
		array( 'leeftijd' => NEW_LEEFTIJD )
	);

	return $form['fields'][0]['mappedFieldValueZGW'];
}

it(
	'remaps a zaakeigenschap onto the new zaaktype version by name',
	function () {
		expect( migrate_mapping( OLD_LEEFTIJD ) )->toBe( NEW_LEEFTIJD );
	}
);

it(
	'leaves mappings untouched that cannot be migrated',
	function ( string $mapped ) {
		expect( migrate_mapping( $mapped ) )->toBe( $mapped );
	}
)->with(
	array(
		'no mapping'                      => array( '' ),
		'not a zaakeigenschap'            => array( 'omschrijving' ),
		'already on the new version'      => array( NEW_LEEFTIJD ),
		'name missing in the new version' => array( OLD_KLEUR ),
		'unknown in the previous version' => array( 'https://catalogi.example.com/eigenschappen/unknown' ),
	)
);

it(
	'returns the form unchanged when it has no fields',
	function () {
		expect( ZaakeigenschappenMigrator::remap_fields( array( 'id' => 1 ), array(), array() ) )->toBe( array( 'id' => 1 ) );
	}
);
