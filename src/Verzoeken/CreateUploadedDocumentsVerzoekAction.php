<?php

declare(strict_types=1);

/**
 * Create uploaded documents action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Verzoeken;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GF_Field;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Traits\InformationObject;
use OWCGravityFormsZGW\Traits\MergeTagTranslator;

/**
 * Create uploaded documents action.
 *
 * @since 1.0.0
 */
class CreateUploadedDocumentsVerzoekAction
{
	use InformationObject;
	use MergeTagTranslator;

	protected array $entry;
	protected array $form;
	protected array $verzoek;

	public function __construct(array $entry, array $form, array $verzoek )
	{
		$this->entry   = $entry;
		$this->form    = $form;
		$this->verzoek = $verzoek;
	}

	public function add_uploaded_documents(): ?bool
	{
		$mapped_args = $this->get_mapped_required_information_object_creation_args();

		if ( ! is_array( $mapped_args['informatieobject'] ?? false ) || array() === $mapped_args['informatieobject'] ) {
			return null; // No files mapped → not an error
		}

		$count   = count( $mapped_args['informatieobject'] );
		$success = 0;

		foreach ( $mapped_args['informatieobject'] as $object ) {
			$args = $this->prepare_information_object_args(
				$object['url'],
				$object['type'],
				$object['description']
			);

			$connection_result = $this->connect_object_to_information_object(
				$this->create_information_object( $args )
			);

			if ( isset( $connection_result['uuid'] ) && '' !== $connection_result['uuid'] ) {
				++$success;
			}
		}

		return $count === $success; // true = all succeeded, false = partial failure.
	}

	/**
	 * Adds form field values from fields linked to the "informatieobject" mapping key
	 * to the arguments required for creating an "informatieobject".
	 * The mapping is based on the relationship between argument keys
	 * and form fields via their mappedFieldValueZGW values.
	 */
	protected function get_mapped_required_information_object_creation_args(): array
	{
		$args = array( 'informatieobject' => array() );

		foreach ( $this->form['fields'] as $field ) {
			if ( empty( $field->mappedFieldValueZGW ) || 'informatieobject' !== $field->mappedFieldValueZGW || empty( $field->mappedFieldDocumentTypeValueZGW ) ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( empty( $field_value ) ) {
				continue;
			}

			$args = $this->map_information_object_arg( $args, $field, $field_value );
		}

		return $args;
	}

	/**
	 * Fields mapped to 'informatieobject' can contain a simple url but also an array of urls in JSON format.
	 */
	protected function map_information_object_arg(array $args, GF_Field $field, $field_value ): array
	{
		$start = substr( $field_value, 0, 1 );
		$end   = substr( $field_value, -1, 1 );

		// Check if the field value is an array in JSON format and decode.
		if ( '[' === $start && ']' === $end ) {
			$field_value = $this->parse_information_object_json( $field_value, $field );
		}

		if ( is_string( $field_value ) ) {
			$field_value = array(
				array(
					'type'        => $field->mappedFieldDocumentTypeValueZGW,
					'url'         => $field_value,
					'description' => $field->uploadFieldDescriptionValueZGW ?? '',
				),
			);
		}

		// After previous conversions, it's possible the value is empty.
		if ( empty( $field_value ) ) {
			return $args;
		}

		$args[ $field->mappedFieldValueZGW ] = array_merge(
			$args[ $field->mappedFieldValueZGW ] ?? array(),
			$field_value
		);

		return $args;
	}

	/**
	 * Converts a JSON-encoded array of URLs into an array of information object structures,
	 * each with a document type and URL.
	 */
	protected function parse_information_object_json(string $field_value, GF_Field $field ): array
	{
		$field_values = json_decode( $field_value );

		if ( empty( $field_values ) || ! is_array( $field_values ) ) {
			return array();
		}

		return array_map(
			function ($field_value ) use ($field ) {
				return array(
					'type'        => $field->mappedFieldDocumentTypeValueZGW,
					'url'         => $field_value,
					'description' => $field->uploadFieldDescriptionValueZGW ?? '',
				);
			},
			$field_values
		);
	}

	protected function prepare_information_object_args(string $object_url, string $information_object_type, string $object_description = '' ): array
	{
		if ( 1 > strlen( $information_object_type ) ) {
			return array();
		}

		$file_name    = $this->create_file_name( $object_url );
		$file_size    = $this->get_content_length( $object_url );
		$file_content = $this->encode_base64_from_url( $object_url );

		$args                                = array();
		$args['titel']                       = $file_name;
		$args['formaat']                     = $this->get_content_type( $object_url );
		$args['bestandsnaam']                = sprintf( '%s.%s', sanitize_title( $file_name ), $this->get_extension( $object_url ) );
		$args['bestandsomvang']              = $file_size ? (int) $file_size : strlen( $file_content );
		$args['beschrijving']                = 0 < strlen( $object_description ) ? $this->translate_merge_tags( $this->entry, $this->form, $object_description ) : $file_name;
		$args['inhoud']                      = $file_content;
		$args['vertrouwelijkheidaanduiding'] = 'vertrouwelijk';
		$args['auteur']                      = 'OWC';
		$args['status']                      = 'gearchiveerd';
		$args['taal']                        = 'nld';
		$args['bronorganisatie']             = ContainerResolver::make()->get( 'zgw.rsin' );
		$args['creatiedatum']                = date( 'Y-m-d' );
		$args['informatieobjecttype']        = $information_object_type;

		return $args;
	}

	protected function create_file_name(string $object_url ): string
	{
		$path_info = pathinfo( $object_url );
		$file_name = $path_info['filename'];

		return sprintf( '%s_%s', uniqid(), $file_name );
	}

	protected function create_information_object(array $args ): ?array
	{
		if ( empty( $args ) ) {
			return null;
		}

		$information_object               = ( new Client() )->create_information_object( $args );
		$information_object['object_url'] = $this->verzoek['url']; // Is required for connecting an "informatieobject" to a "zaak".

		return $information_object;
	}

	protected function connect_object_to_information_object(?array $information_object ): ?array
	{
		if ( empty( $information_object ) ) {
			return null;
		}

		$data['informatieobject'] = $information_object['url'];
		$data['object']           = $information_object['object_url'];
		$data['objectType']       = 'besluit';

		return ( new Client() )->connect_object_to_information_object( $data );
	}
}
