<?php

use OWCGravityFormsZGW\GravityForms\ZaakeigenschappenMigrator;

const OLD_LEEFTIJD = 'https://catalogi.example.com/eigenschappen/old-leeftijd';
const OLD_KLEUR    = 'https://catalogi.example.com/eigenschappen/old-kleur';
const NEW_LEEFTIJD = 'https://catalogi.example.com/eigenschappen/new-leeftijd';

/**
 * Migrates a single field against a current zaaktype version that only has "leeftijd".
 * Outdated URLs resolve to a name as if fetched from the API.
 */
function migrate_field( array $field ): array
{
	$form = ZaakeigenschappenMigrator::remap_fields(
		array( 'fields' => array( $field ) ),
		array( NEW_LEEFTIJD => 'leeftijd' ),
		fn ( string $url ): string => array(
			OLD_LEEFTIJD => 'leeftijd',
			OLD_KLEUR    => 'kleur',
		)[ $url ] ?? ''
	);

	return $form['fields'][0];
}

function migrate_mapping( string $mapped ): string
{
	return migrate_field( array( 'mappedFieldValueZGW' => $mapped ) )['mappedFieldValueZGW'];
}

it(
	'remaps a zaakeigenschap onto the current zaaktype version by its resolved name',
	function () {
		expect( migrate_field( array( 'mappedFieldValueZGW' => OLD_LEEFTIJD ) ) )->toBe(
			array(
				'mappedFieldValueZGW' => NEW_LEEFTIJD,
				'mappedFieldNameZGW'  => 'leeftijd',
			)
		);
	}
);

it(
	'prefers the stored name over resolving it',
	function () {
		$field = array(
			'mappedFieldValueZGW' => 'https://catalogi.example.com/eigenschappen/gone',
			'mappedFieldNameZGW'  => 'leeftijd',
		);

		expect( migrate_field( $field )['mappedFieldValueZGW'] )->toBe( NEW_LEEFTIJD );
	}
);

it(
	'stores the name of a valid mapping',
	function () {
		expect( migrate_field( array( 'mappedFieldValueZGW' => NEW_LEEFTIJD ) )['mappedFieldNameZGW'] )->toBe( 'leeftijd' );
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
		'already on the current version'  => array( NEW_LEEFTIJD ),
		'name missing in current version' => array( OLD_KLEUR ),
		'name cannot be resolved'         => array( 'https://catalogi.example.com/eigenschappen/unknown' ),
	)
);

it(
	'returns the form unchanged when it has no fields',
	function () {
		expect( ZaakeigenschappenMigrator::remap_fields( array( 'id' => 1 ), array(), fn () => '' ) )->toBe( array( 'id' => 1 ) );
	}
);

it(
	'detects changed mappings through the signature',
	function () {
		$form     = array( 'fields' => array( array( 'mappedFieldValueZGW' => OLD_LEEFTIJD ) ) );
		$migrated = ZaakeigenschappenMigrator::remap_fields( $form, array( NEW_LEEFTIJD => 'leeftijd' ), fn () => 'leeftijd' );

		expect( ZaakeigenschappenMigrator::mapping_signature( $migrated ) )->not->toBe( ZaakeigenschappenMigrator::mapping_signature( $form ) );
	}
);
