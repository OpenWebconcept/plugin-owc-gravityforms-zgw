<?php

declare(strict_types=1);

/**
 * Abstract create "zaak" action.
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

use DateTime;
use Exception;
use GF_Field;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Endpoints\Filter\RoltypenFilter;
use OWC\ZGW\Entities\Rol;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakeigenschap;
use OWC\ZGW\Entities\Zaakobject;
use OWC\ZGW\Http\Errors\BadRequestError;
use OWC\ZGW\Support\PagedCollection;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Enums\BetrokkeneType;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\GravityForms\Traits\EntryNote;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Services\EncryptionService;
use OWCGravityFormsZGW\Traits\CastValue;
use OWCGravityFormsZGW\Traits\MergeTagTranslator;
use OWCGravityFormsZGW\Transactions\Controllers\TransactionController;
use WP_Post;
use function OWC\ZGW\apiClient;

/**
 * Abstract create "zaak" action.
 *
 * @since 1.0.0
 */
abstract class AbstractCreateZaakAction
{
	use EntryNote;
	use CastValue;
	use MergeTagTranslator;

	private const INVOLVED_IDENTIFICATION_MAPPING_KEYS_TO_TYPE = array(
		'inpBsn'                   => 'string',
		'anpIdentificatie'         => 'string',
		'inpA_nummer'              => 'string',
		'geslachtsnaam'            => 'string',
		'voorvoegselGeslachtsnaam' => 'string',
		'voorletters'              => 'string',
		'voornamen'                => 'string',
		'geslachtsaanduiding'      => 'string',
		'geboortedatum'            => 'string',
		'aoaIdentificatie'         => 'string',
		'wplWoonplaatsNaam'        => 'string',
		'gorOpenbareRuimteNaam'    => 'string',
		'aoaPostcode'              => 'string',
		'aoaHuisnummer'            => 'int',
		'aoaHuisletter'            => 'string',
		'aoaHuisnummertoevoeging'  => 'string',
		'inpLocatiebeschrijving'   => 'string',
		'lndLandcode'              => 'string',
		'lndLandnaam'              => 'string',
		'subAdresBuitenland_1'     => 'string',
		'subAdresBuitenland_2'     => 'string',
		'subAdresBuitenland_3'     => 'string',
	);

	private const ADDRESS_OBJECT_MAPPING_KEYS_TO_TYPE = array(
		'wplWoonplaatsNaam'     => 'string',
		'gorOpenbareRuimteNaam' => 'string',
		'huisnummer'            => 'string',
		'huisletter'            => 'string',
		'huisnummertoevoeging'  => 'string',
		'postcode'              => 'string',
	);

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected Client $client;
	protected LoggerZGW $logger;

	public function __construct( array $entry, array $form, array $supplier_config )
	{
		$this->entry         = $entry;
		$this->form          = $form;
		$this->supplier_name = $supplier_config['name'] ?? '';
		$this->client        = apiClient( $supplier_config['name'] ?? '' );
		$this->logger        = ContainerResolver::make()->get( 'logger.zgw' );
	}

	abstract public function create(): Zaak;

	/**
	 * Merge mapped arguments with defaults.
	 */
	protected function get_mapped_required_zaak_creation_args(): array
	{
		$args = array(
			'bronorganisatie'              => ContainerResolver::make()->get( 'zgw.rsin' ),
			'omschrijving'                 => '',
			'registratiedatum'             => date( 'Y-m-d' ),
			'startdatum'                   => date( 'Y-m-d' ),
			'verantwoordelijkeOrganisatie' => ContainerResolver::make()->get( 'zgw.rsin' ),
			'zaaktype'                     => FormUtils::zaaktype_identifier_form_setting( $this->form, $this->supplier_name ),
		);

		return $this->map_required_zaak_creation_args( $args );
	}

	/**
	 * Add form field values to arguments required for creating a "zaak".
	 * The mapping is based on the relationship between argument keys
	 * and form fields via their mappedFieldValueZGW values.
	 */
	protected function map_required_zaak_creation_args( array $args ): array
	{
		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueZGW ) || ! is_string( $field->mappedFieldValueZGW ) || '' === $field->mappedFieldValueZGW || ! isset( $args[ $field->mappedFieldValueZGW ] ) ) {
				continue;
			}

			$field_value = $this->handle_zaak_creation_arg_value( $field );

			if ( '' === $field_value ) {
				continue;
			}

			if ( 'date' === $field->type ) {
				try {
					$field_value = ( new DateTime( $field_value ) )->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					continue;
				}
			}

			$args[ $field->mappedFieldValueZGW ] = $this->translate_merge_tags( $this->entry, $this->form, $field_value );
		}

		return $args;
	}

	/**
	 * Handle getting the value for a "zaak" creation argument from multiple form fields and types.
	 * Checkboxes for example can have multiple inputs.
	 *
	 * @since 1.2.0
	 */
	protected function handle_zaak_creation_arg_value( GF_Field $field ): string
	{
		if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
			$field_value  = '';
			$input_values = array();

			foreach ( $field->inputs as $input ) {
				$input_id = (string) ( $input['id'] ?? '' );

				if ( '' === $input_id ) {
					continue;
				}

				$input_value = rgar( $this->entry, $input_id );

				if ( ! is_string( $input_value ) || '' === $input_value ) {
					continue;
				}

				$input_values[] = trim( $input_value );
			}

			$count = count( $input_values );

			$field_value = match ( true ) {
				0 === $count => '',
				1 === $count => $input_values[0],
				2 === $count => implode( ' en ', $input_values ),
				2 < $count => implode( ', ', array_slice( $input_values, 0, -1 ) ) . ' en ' . end( $input_values ),
			};
		} else {
			$field_value = rgar( $this->entry, (string) $field->id );
		}

		return is_string( $field_value ) && '' !== $field_value ? $field_value : '';
	}

	/**
	 * @since 1.9.1
	 */
	protected function add_address_object_to_zaak( Zaak $zaak ): void
	{
		$address_object_args = $this->map_address_object_args( $zaak );

		if ( 0 === count( $address_object_args ) ) {
			return;
		}

		try {
			$this->client->zaakobjecten()->create( new Zaakobject( $address_object_args, $this->client ) );
		} catch ( BadRequestError $e ) {
			$this->logger->error( sprintf( 'Failed to add address object to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), json_encode( $e->getInvalidParameters() ) ) );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Failed to add address object to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), $e->getMessage() ) );
		}
	}

	/**
	 * @since 1.9.1
	 */
	private function map_address_object_args( Zaak $zaak ): array
	{
		$args = array();

		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueAddressObjectZGW ) || ! is_string( $field->mappedFieldValueAddressObjectZGW ) || '' === $field->mappedFieldValueAddressObjectZGW || ! isset( self::ADDRESS_OBJECT_MAPPING_KEYS_TO_TYPE[ $field->mappedFieldValueAddressObjectZGW ] ) ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( is_string( $field_value ) && '' !== $field_value ) {
				$args[ $field->mappedFieldValueAddressObjectZGW ] = $this->cast_value( self::ADDRESS_OBJECT_MAPPING_KEYS_TO_TYPE[ $field->mappedFieldValueAddressObjectZGW ], $field_value );
			}
		}

		if ( 0 === count( $args ) ) {
			return array();
		}

		return array(
			'zaak'                => $zaak->url,
			'objectType'          => 'adres',
			'objectIdentificatie' => array_merge(
				array(
					'identificatie' => sprintf( 'zgw_adr_obj_%s', $zaak->getValue( 'identificatie', '' ) ),
				),
				$args
			),
		);
	}

	/**
	 * Assign a submitter to the created "zaak".
	 */
	public function add_rol_to_zaak( Zaak $zaak ): ?Rol
	{
		$rol_types = $this->get_rol_types();

		if ( $rol_types->isEmpty() ) {
			throw new Exception( 'No role types found for this "zaaktype"', 400 );
		}

		$current_bsn = $this->get_bsn_for_zaak_rol();
		$current_kvk = $this->get_kvk_for_zaak_rol();

		if ( ( ! is_string( $current_bsn ) || '' === $current_bsn ) && ( ! is_string( $current_kvk ) || '' === $current_kvk ) ) {
			throw new Exception( 'This session appears to have no BSN or KVK', 400 );
		}

		foreach ( $rol_types as $rol_type ) {
			if ( 'initiator' !== strtolower( $rol_type['omschrijvingGeneriek'] ) ) {
				continue;
			}

			$args = array(
				'roltoelichting' => strtolower( $rol_type['omschrijvingGeneriek'] ),
				'roltype'        => $rol_type['url'],
				'zaak'           => $zaak->url,
			);

			$args = $this->add_identification_to_rol_args( $args, $current_bsn, $current_kvk );
			$args = $this->add_contact_person_rol_to_rol_args( $args );
			$args = $this->add_involved_identification_to_rol_args( $args );

			try {
				$rol = $this->client->rollen()->create( new Rol( $args, $this->client ) );
			} catch ( BadRequestError $e ) {
				$this->logger->error( sprintf( 'Failed to add rol to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), json_encode( $e->getInvalidParameters() ) ) );
			} catch ( Exception $e ) {
				$this->logger->error( sprintf( 'Failed to add rol to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), $e->getMessage() ) );
			}

			break;
		}

		return $rol ?? null;
	}

	/**
	 * Get all available "roltypen" based on the given "zaaktype".
	 */
	public function get_rol_types(): PagedCollection
	{
		$filter = new RoltypenFilter();
		$filter->add( 'zaaktype', FormUtils::zaaktype_identifier_form_setting( $this->form, $this->supplier_name ) );

		return $this->client->roltypen()->filter( $filter );
	}

	/**
	 * Retrieve BSN from the current user or from the overwrite form setting.
	 *
	 * @since 1.2.0
	 */
	private function get_bsn_for_zaak_rol(): ?string
	{
		$bsn_overwrite = FormUtils::overwrite_bsn_form_setting( $this->form );

		if ( is_string( $bsn_overwrite ) && '' !== $bsn_overwrite ) {
			return $bsn_overwrite;
		}

		return $this->get_identification_from_transaction( meta_field: 'transaction_user_bsn' );
	}

	/**
	 * Retrieve KVK from the current user or from the overwrite form setting.
	 *
	 * @since 1.2.0
	 */
	private function get_kvk_for_zaak_rol(): ?string
	{
		$kvk_overwrite = FormUtils::overwrite_kvk_form_setting( $this->form );

		if ( is_string( $kvk_overwrite ) && '' !== $kvk_overwrite ) {
			return $kvk_overwrite;
		}

		return $this->get_identification_from_transaction( meta_field: 'transaction_user_kvk' );
	}

	/**
	 * Retrieve the decrypted identification value from the related transaction post.
	 *
	 * A transaction post is created immediately upon form submission, outside of the
	 * Action Scheduler process. When executed within the Action Scheduler, the current
	 * user context is not available because it runs in a separate background process.
	 * Therefore, the identification must be retrieved from the associated transaction post.
	 *
	 * @since 1.5.2
	 */
	private function get_identification_from_transaction( string $meta_field )
	{
		$args = array(
			'post_type'   => TransactionController::POST_TYPE,
			'post_status' => 'any',
			'meta_query'  => array(
				array(
					'key'     => 'transaction_entry_id',
					'value'   => $this->entry['id'],
					'compare' => '=',
				),
			),
		);

		$transaction_posts = get_posts( $args );

		if ( ! ( $transaction_posts[0] ?? null ) instanceof WP_Post ) {
			return null;
		}

		$transaction_post         = $transaction_posts[0];
		$encrypted_identification = get_post_meta( $transaction_post->ID, $meta_field, true );

		if ( ! is_string( $encrypted_identification ) || '' === $encrypted_identification ) {
			return null;
		}

		try {
			$decrypted_identification = EncryptionService::decrypt( $encrypted_identification );

			if ( ! is_string( $decrypted_identification ) || '' === $decrypted_identification ) {
				throw new Exception( 'Decrypted identification is empty or not a string' );
			}

			return $decrypted_identification;
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Failed to decrypt identification for entry "%s": %s (%s)', $this->entry['id'], $e->getMessage(), $encrypted_identification ) );

			return null;
		}
	}

	/**
	 * @since 1.2.0
	 */
	private function add_identification_to_rol_args( array $args, ?string $current_bsn, ?string $current_kvk ): array
	{
		if ( is_string( $current_bsn ) && '' !== $current_bsn ) {
			$args['betrokkeneType']                    = BetrokkeneType::NATUURLIJK_PERSOON->value;
			$args['betrokkeneIdentificatie']['inpBsn'] = $current_bsn;
		} elseif ( is_string( $current_kvk ) && '' !== $current_kvk ) {
			$args['betrokkeneType']                              = BetrokkeneType::VESTIGING->value;
			$args['betrokkeneIdentificatie']['kvkNummer']        = $current_kvk;
			$args['betrokkeneIdentificatie']['vestigingsNummer'] = $this->possible_branch_number_kvk();
		}

		return $args;
	}

	/**
	 * @since 1.8.0
	 */
	private function add_contact_person_rol_to_rol_args( array $args ): array
	{
		$mapped_args = array();

		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueContactPersonRoleZGW ) || ! is_string( $field->mappedFieldValueContactPersonRoleZGW ) || '' === $field->mappedFieldValueContactPersonRoleZGW ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( '' === $field_value ) {
				continue;
			}

			$mapped_args[ $field->mappedFieldValueContactPersonRoleZGW ] = $field_value;
		}

		if ( 0 < count( $mapped_args ) ) {
			$args['contactpersoonRol'] = $mapped_args;
		}

		return $args;
	}

	/**
	 * @since 1.8.0
	 */
	private function add_involved_identification_to_rol_args( array $args ): array
	{
		$mapped_args = array();

		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueInvolvedIdentificationZGW ) || ! is_string( $field->mappedFieldValueInvolvedIdentificationZGW ) || '' === $field->mappedFieldValueInvolvedIdentificationZGW ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( '' === $field_value ) {
				continue;
			}

			if ( 'date' === $field->type ) {
				try {
					$field_value = ( new DateTime( $field_value ) )->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					continue;
				}
			}

			$is_nested = str_contains( $field->mappedFieldValueInvolvedIdentificationZGW, '.' );

			if ( $is_nested ) {
				$this->array_set_by_dot_notated_path( $mapped_args, $field->mappedFieldValueInvolvedIdentificationZGW, $field_value );

				continue;
			}

			$type = self::INVOLVED_IDENTIFICATION_MAPPING_KEYS_TO_TYPE[ $field->mappedFieldValueInvolvedIdentificationZGW ] ?? 'string';
			$mapped_args[ $field->mappedFieldValueInvolvedIdentificationZGW ] = $this->cast_value( $type, $field_value );
		}

		if ( 0 < count( $mapped_args ) ) {
			$args['betrokkeneIdentificatie'] = array_merge( $args['betrokkeneIdentificatie'] ?? array(), $mapped_args );
		}

		return $args;
	}

	/**
	 * Set a value in a multi-dimensional array using "dot" notation for the keys.
	 *
	 * @since 1.8.0
	 */
	private function array_set_by_dot_notated_path( array &$array, string $path, $value ): void
	{
		$keys     = explode( '.', $path );
		$current  = &$array;
		$last_key = array_pop( $keys );

		if ( ! is_string( $last_key ) || $last_key === '' ) {
			return;
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $current[ $key ] ) || ! is_array( $current[ $key ] ) ) {
				$current[ $key ] = array();
			}

			$current = &$current[ $key ];
		}

		$type                 = self::INVOLVED_IDENTIFICATION_MAPPING_KEYS_TO_TYPE[ $last_key ] ?? 'string';
		$current[ $last_key ] = $this->cast_value( $type, $value );
	}

	/**
	 * Checks if any form field is mapped to the KVK branch number and returns its value.
	 *
	 * @since 1.3.0
	 */
	protected function possible_branch_number_kvk(): string
	{
		return $this->get_value_of_mapped_field( 'linkedFieldValueKvKBranchNumber' );
	}

	/**
	 * Checks if any form field is mapped to the given ZGW property and returns its value.
	 *
	 * A field is considered "mapped" when the given property is set to '1' on the field.
	 *
	 * @since 1.8.0
	 */
	private function get_value_of_mapped_field( string $property ): string
	{
		if ( ! isset( $this->form['fields'] ) || ! is_array( $this->form['fields'] ) ) {
			return '';
		}

		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->$property ) || '1' !== $field->$property ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( is_string( $field_value ) && '' !== $field_value ) {
				return $field_value;
			}
		}

		return '';
	}

	public function create_zaak_properties( Zaak $zaak ): void
	{
		$zaak_properties = $this->map_zaak_properties_args();

		foreach ( $zaak_properties as $zaak_property ) {
			if ( empty( $zaak_property['eigenschap'] ) || empty( $zaak_property['waarde'] ) ) {
				continue;
			}

			$property = array(
				'zaak'       => $zaak->url,
				'eigenschap' => $zaak_property['eigenschap'],
				'waarde'     => $zaak_property['waarde'],
			);

			try {
				$this->client->zaakeigenschappen()->create(
					$zaak,
					new Zaakeigenschap( $property, $this->client )
				);
			} catch ( BadRequestError $e ) {
				$this->logger->error( sprintf( 'Failed to create zaak property for zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), json_encode( $e->getInvalidParameters() ) ) );

			} catch ( Exception $e ) {
				$this->logger->error( sprintf( 'Failed to create zaak property for zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), $e->getMessage() ) );
			}
		}
	}

	/**
	 * Add form field values to arguments for creating "zaak" properties.
	 * Mapping is done by the relation between argument keys and form fields mappedFieldValueZGWs.
	 */
	protected function map_zaak_properties_args(): array
	{
		$mapped_fields = array();

		foreach ( $this->form['fields'] as $field ) {
			if ( empty( $field->mappedFieldValueZGW ) || strpos( $field->mappedFieldValueZGW, 'https://' ) === false ) {
				continue;
			}

			$property_value = rgar( $this->entry, (string) $field->id );

			if ( empty( $property_value ) ) {
				continue;
			}

			if ( 'date' === $field->type ) {
				$property_value = $this->handle_zaak_date_property( $property_value );
			}

			$mapped_fields[ $field->id ] = array(
				'eigenschap' => $field->mappedFieldValueZGW,
				'waarde'     => $property_value,
			);
		}

		return $mapped_fields;
	}

	private function handle_zaak_date_property( string $property_value ): string
	{
		try {
			$property_value = ( new DateTime( $property_value ) )->format( 'Y-m-d' );
		} catch ( Exception $e ) {
			$property_value = '0000-00-00';
		}

		return $property_value;
	}

	/**
	 * Store generated "zaak" information in the entry's metadata.
	 * Could be used for further processing or debugging.
	 */
	protected function add_created_zaak_as_entry_meta( Zaak $zaak ): void
	{
		gform_update_meta( $this->entry['id'], 'owc_gz_created_zaak_url', $zaak->url ?? null );
		gform_update_meta( $this->entry['id'], 'owc_gz_created_zaak_uuid', $zaak->uuid ?? null );
		gform_update_meta( $this->entry['id'], 'owc_gz_created_zaak_id', $zaak->identificatie ?? null );

		// translators: %1$s is the UUID of the created zaak, %2$s is the identificatie of the created zaak.
		$this->add_entry_note( $this->entry['id'], sprintf( __( 'Zaak "%1$s" aangemaakt onder zaak nummer "%2$s".', 'owc-gravityforms-zgw' ), $zaak->uuid ?? __( 'UUID onbekend', 'owc-gravityforms-zgw' ), $zaak->identificatie ?? __( 'ID onbekend', 'owc-gravityforms-zgw' ) ), 'success' );
	}
}
