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

use DateTimeImmutable;
use DatetimeZone;
use Exception;
use GF_Field;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\Enums\BetrokkeneType;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Traits\MergeTagTranslator;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Endpoints\Filter\RoltypenFilter;
use OWC\ZGW\Entities\Rol;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakeigenschap;
use OWC\ZGW\Http\Errors\BadRequestError;
use OWC\ZGW\Support\PagedCollection;
use function OWC\ZGW\apiClient;

/**
 * Abstract create "zaak" action.
 *
 * @since 1.0.0
 */
abstract class AbstractCreateZaakAction
{
	use MergeTagTranslator;

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected Client $client;
	protected LoggerZGW $logger;

	public function __construct(array $entry, array $form, array $supplier_config )
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
	protected function map_required_zaak_creation_args(array $args ): array
	{
		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->mappedFieldValueZGW ) || ! is_string( $field->mappedFieldValueZGW ) || '' === $field->mappedFieldValueZGW || ! isset( $args[ $field->mappedFieldValueZGW ] ) ) {
				continue;
			}

			$field_value = $this->handle_zaak_creation_arg_value( $field );

			if ( empty( $field_value ) ) {
				continue;
			}

			if ( 'date' === $field->type ) {
				$field_value = $this->handle_date_field( $field );
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
	protected function handle_zaak_creation_arg_value(GF_Field $field ): string
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
	 * Assign a submitter to the created "zaak".
	 */
	public function add_rol_to_zaak(Zaak $zaak ): ?Rol
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
			if ( 'initiator' !== $rol_type['omschrijvingGeneriek'] ) {
				continue;
			}

			$args = array(
				'roltoelichting' => $rol_type['omschrijvingGeneriek'],
				'roltype'        => $rol_type['url'],
				'zaak'           => $zaak->url,
			);

			$args = $this->add_identification_to_rol_args( $args, $current_bsn, $current_kvk );

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

		return ContainerResolver::make()->get( 'digid.current_user_bsn' );
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

		return ContainerResolver::make()->get( 'eherkenning.current_user_kvk' );
	}

	/**
	 * @since 1.2.0
	 */
	private function add_identification_to_rol_args(array $args, ?string $current_bsn, ?string $current_kvk ): array
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
	 * @since 1.3.0
	 *
	 * Checks if any form field is mapped to the KVK branch number and return its value.
	 */
	protected function possible_branch_number_kvk(): string
	{
		foreach ( $this->form['fields'] as $field ) {
			if ( ! isset( $field->linkedFieldValueKvKBranchNumber ) || '1' !== $field->linkedFieldValueKvKBranchNumber ) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if ( is_string( $field_value ) || '' !== $field_value ) {
				return $field_value;
			}
		}

		return '';
	}

	public function create_zaak_properties(Zaak $zaak ): void
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
				$property_value = $this->handle_date_field( $field );
			}

			$mapped_fields[ $field->id ] = array(
				'eigenschap' => $field->mappedFieldValueZGW,
				'waarde'     => $property_value,
			);
		}

		return $mapped_fields;
	}

	/**
	 * Handle date fields by validating and formatting the date according to the expected format.
	 * Also handles the option to include time in the value which is set to 12:00 to avoid timezone issues since the date will be stored in UTC.
	 * If the date is invalid, return a default value and log an error.
	 *
	 * @since 1.4.0
	 */
	protected function handle_date_field(GF_Field $field ): string
	{
		$input         = trim( (string) rgar( $this->entry, (string) $field->id ) );
		$wants_time    = ( '1' === ( $field->linkedFieldValueUseTimestamp ?? '0' ) );
		$return_format = $wants_time ? 'Y-m-d H:i' : 'Y-m-d';

		// Include the '!' character in the format to ensure that missing date parts are set to their default values instead of being filled with current date values.
		$date       = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone( 'UTC' ) );
		$errors     = DateTimeImmutable::getLastErrors();
		$has_errors = is_array( $errors ) && ( ( $errors['warning_count'] ?? 0 ) > 0 || ( $errors['error_count'] ?? 0 ) > 0 );

		if ( ! $date instanceof DateTimeImmutable || $has_errors ) {
			$details = '';

			if ( is_array( $errors ) ) {
				$all = array_merge( $errors['warnings'] ?? array(), $errors['errors'] ?? array() );
				if ( $all ) {
					$details = ' Details: ' . implode( ' | ', array_map( 'strval', $all ) );
				}
			}

			$this->logger->error(
				sprintf(
					'Invalid date for field "%s" (id %s): "%s". Expected format: "%s".%s',
					$field->label ?? '(no label)',
					(string) $field->id,
					$input,
					$field->dateFormat,
					$details
				)
			);

			return $wants_time ? '0000-00-00 00:00' : '0000-00-00';
		}

		if ( $wants_time ) {
			$date = $date->setTime( 12, 0, 0 ); // Set time to 12:00 to avoid timezone issues since the date will be stored in UTC
		}

		return $date->format( $return_format );
	}

	/**
	 * Store generated "zaak" information in the entry's metadata.
	 * Could be used for further processing or debugging.
	 */
	protected function add_created_zaak_as_entry_meta(Zaak $zaak ): void
	{
		add_action(
			'gform_after_submission',
			function (array $entry, array $form ) use ($zaak ) {
				gform_update_meta( $entry['id'], 'owc_gz_created_zaak_url', $zaak->url ?? null );
				gform_update_meta( $entry['id'], 'owc_gz_created_zaak_uuid', $zaak->uuid ?? null );
			},
			10,
			2
		);
	}
}
