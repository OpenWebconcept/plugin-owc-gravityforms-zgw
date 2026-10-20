<?php

declare(strict_types=1);

/**
 * Abstract create uploaded documents action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Contracts;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use GF_Field;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Entities\Enkelvoudiginformatieobject;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakinformatieobject;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\Traits\InformationObject;
use OWCGravityFormsZGW\Traits\MergeTagTranslator;
use function OWC\ZGW\apiClient;

/**
 * Abstract create uploaded documents action.
 *
 * @since 1.0.0
 */
abstract class AbstractCreateUploadedDocumentsAction
{
	use InformationObject;
	use MergeTagTranslator;

	protected array $entry;
	protected array $form;
	protected string $supplier_key;
	protected string $supplier_name;
	protected Zaak $zaak;
	protected Client $client;

	public function __construct( array $entry, array $form, array $supplier_config, Zaak $zaak )
	{
		$this->entry         = $entry;
		$this->form          = $form;
		$this->supplier_key  = $supplier_config['client_type'] ?? '';
		$this->supplier_name = $supplier_config['name'] ?? '';
		$this->zaak          = $zaak;
		$this->client        = apiClient( $this->supplier_name );
	}

	abstract public function add_uploaded_documents(): ?bool;

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

			// Only file upload fields contain server-side generated URLs, other field values are user controlled (SSRF).
			if ( 'fileupload' !== $field->get_input_type() ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( empty( $field_value ) ) {
				continue;
			}

			$args = $this->map_information_object_arg( $args, $field, $field_value );
		}

		if ( $this->use_document_type_file_names() ) {
			$args['informatieobject'] = $this->add_document_type_file_names( $args['informatieobject'] );
		}

		return $args;
	}

	/**
	 * Fields mapped to 'informatieobject' can contain a simple url but also an array of urls in JSON format.
	 */
	protected function map_information_object_arg( array $args, GF_Field $field, $field_value ): array
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
	 * Whether uploads should be named after their document type, configured on the plugin settings page.
	 *
	 * @since NEXT
	 */
	protected function use_document_type_file_names(): bool
	{
		return ContainerResolver::make()->get( 'zgw.site_options' )->document_type_file_names();
	}

	/**
	 * Names each upload after its document type.
	 * When multiple uploads in the form share a document type, a sequence number is added, e.g. documenttype_1 and documenttype_2.
	 *
	 * @since NEXT
	 */
	protected function add_document_type_file_names( array $objects ): array
	{
		$names   = array();
		$grouped = array();

		foreach ( $objects as $index => $object ) {
			$type = (string) ( $object['type'] ?? '' );

			if ( ! array_key_exists( $type, $names ) ) {
				$names[ $type ] = $this->get_document_type_name( $type );
			}

			if ( '' === $names[ $type ] ) {
				continue; // Falls back to the default file name.
			}

			$grouped[ $names[ $type ] ][] = $index;
		}

		foreach ( $grouped as $name => $indexes ) {
			foreach ( array_values( $indexes ) as $number => $index ) {
				$objects[ $index ]['file_name'] = 1 < count( $indexes ) ? sprintf( '%s_%d', $name, $number + 1 ) : (string) $name;
			}
		}

		return $objects;
	}

	/**
	 * Retrieves the description ("omschrijving") of the document type ("informatieobjecttype").
	 * Uses the cached document types of the supplier first, the API is only requested when the cache misses.
	 *
	 * @since NEXT
	 */
	protected function get_document_type_name( string $document_type_url ): string
	{
		$cached_name = $this->get_cached_document_type_name( $document_type_url );

		if ( '' !== $cached_name ) {
			return $cached_name;
		}

		$identifier = basename( (string) wp_parse_url( $document_type_url, PHP_URL_PATH ) );

		if ( '' === $identifier ) {
			return '';
		}

		try {
			$document_type = $this->client->informatieobjecttypen()->get( $identifier );
		} catch ( Exception $e ) {
			ContainerResolver::make()->get( 'logger.zgw' )->error( 'Failed to retrieve document type: ' . $document_type_url, array( 'error' => $e->getMessage() ) );

			return '';
		}

		return trim( (string) ( $document_type->omschrijving ?? '' ) );
	}

	/**
	 * @since NEXT
	 */
	protected function get_cached_document_type_name( string $document_type_url ): string
	{
		$types = get_transient( InformatieobjecttypeAdapter::transient_key( $this->supplier_name ) );

		if ( ! is_array( $types ) ) {
			return '';
		}

		foreach ( $types as $type ) {
			if ( ( $type['value'] ?? '' ) === $document_type_url ) {
				return trim( (string) ( $type['omschrijving'] ?? '' ) );
			}
		}

		return '';
	}

	/**
	 * Converts a JSON-encoded array of URLs into an array of information object structures,
	 * each with a document type and URL.
	 */
	protected function parse_information_object_json( string $field_value, GF_Field $field ): array
	{
		$field_values = json_decode( $field_value );

		if ( empty( $field_values ) || ! is_array( $field_values ) ) {
			return array();
		}

		return array_map(
			function ( $field_value ) use ( $field ) {
				return array(
					'type'        => $field->mappedFieldDocumentTypeValueZGW,
					'url'         => $field_value,
					'description' => $field->uploadFieldDescriptionValueZGW ?? '',
				);
			},
			$field_values
		);
	}

	protected function prepare_information_object_args( string $object_url, string $information_object_type, string $object_description = '', string $custom_file_name = '' ): array
	{
		if ( 1 > strlen( $information_object_type ) ) {
			return array();
		}

		$file_content = $this->encode_base64_from_url( $object_url );

		if ( '' === $file_content ) {
			return array(); // Retrieving the file failed, prevents creating an informatieobject without content.
		}

		$file_name = $this->create_file_name( $object_url, $custom_file_name );
		$file_size = $this->get_content_length( $object_url );

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

	protected function create_file_name( string $object_url, string $custom_file_name = '' ): string
	{
		if ( '' !== $custom_file_name ) {
			return $custom_file_name;
		}

		$path_info = pathinfo( $object_url );
		$file_name = $path_info['filename'];

		return sprintf( '%s_%s', uniqid(), $file_name );
	}

	protected function create_information_object( array $args ): ?Enkelvoudiginformatieobject
	{
		if ( empty( $args ) ) {
			return null;
		}

		$information_object = $this->client->enkelvoudiginformatieobjecten()->create( new Enkelvoudiginformatieobject( $args, $this->client ) );
		$information_object->setValue( 'zaak', $this->zaak->url ); // Is required for connecting an "informatieobject" to a "zaak".

		return $information_object;
	}

	protected function connect_zaak_to_information_object( ?Enkelvoudiginformatieobject $information_object ): ?Zaakinformatieobject
	{
		if ( empty( $information_object ) ) {
			return null;
		}

		return $this->client->zaakinformatieobjecten()->create( new Zaakinformatieobject( $information_object->toArray(), $this->client ) );
	}
}
