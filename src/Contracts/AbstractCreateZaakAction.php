<?php
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
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use DateTime;
use Exception;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\LoggerZGW;
use OWCGravityFormsZGW\Traits\FormSetting;
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
	use FormSetting;
	use MergeTagTranslator;

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected string $supplier_key;
	protected Client $client;
	protected LoggerZGW $logger;

	public function __construct(array $entry, array $form, string $supplier_name, string $supplier_key )
	{
		$this->entry         = $entry;
		$this->form          = $form;
		$this->supplier_name = $supplier_name;
		$this->supplier_key  = $supplier_key;
		$this->client        = apiClient( $this->supplier_name );
		$this->logger        = ContainerResolver::make()->get( 'logger.zgw' );
	}

	abstract public function create(): Zaak;

	/**
	 * Merge mapped arguments with defaults.
	 *
	 * @since 1.0.0
	 */
	protected function get_mapped_required_zaak_creation_args(): array
	{
		$args = array(
			'bronorganisatie'              => ContainerResolver::make()->get( 'zgw.rsin' ),
			'omschrijving'                 => '',
			'registratiedatum'             => date( 'Y-m-d' ),
			'startdatum'                   => date( 'Y-m-d' ),
			'verantwoordelijkeOrganisatie' => ContainerResolver::make()->get( 'zgw.rsin' ),
			'zaaktype'                     => $this->zaaktype_identifier_form_setting( $this->form, $this->supplier_key ),
		);

		return $this->map_required_zaak_creation_args( $args );
	}

	/**
	 * Add form field values to arguments required for creating a "zaak".
	 * The mapping is based on the relationship between argument keys
	 * and form fields via their mappedFieldValueZGW values.
	 *
	 * @since 1.0.0
	 */
	protected function map_required_zaak_creation_args(array $args ): array
	{
		foreach ($this->form['fields'] as $field) {
			if (empty( $field->mappedFieldValueZGW ) || ! isset( $args[ $field->mappedFieldValueZGW ] )) {
				continue;
			}

			$field_value = rgar( $this->entry, (string) $field->id );

			if (empty( $field_value )) {
				continue;
			}

			if ('date' === $field->type) {
				$field_value = ( new DateTime( $field_value ) )->format( 'Y-m-d' );
			}

			$args[ $field->mappedFieldValueZGW ] = $this->translate_merge_tags( $this->entry, $this->form, $field_value );
		}

		return $args;
	}

	/**
	 * Assign a submitter to the created "zaak".
	 *
	 * @since 1.0.0
	 */
	public function add_rol_to_zaak(Zaak $zaak ): ?Rol
	{
		$rol_types = $this->get_rol_types();

		if ($rol_types->isEmpty()) {
			throw new Exception( 'No role types found for this "zaaktype"', 400 );
		}

		$current_bsn = ContainerResolver::make()->get( 'digid.current_user_bsn' );

		if (empty( $current_bsn )) {
			throw new Exception( 'This session appears to have no BSN', 400 );
		}

		foreach ($rol_types as $rol_type) {
			if ('initiator' !== $rol_type['omschrijvingGeneriek']) {
				continue;
			}

			$args = array(
				'betrokkeneIdentificatie' => array(
					'inpBsn' => $current_bsn,
				),
				'betrokkeneType'          => 'natuurlijk_persoon',
				'roltoelichting'          => $rol_type['omschrijvingGeneriek'],
				'roltype'                 => $rol_type['url'],
				'zaak'                    => $zaak->url,
			);

			try {
				$rol = $this->client->rollen()->create( new Rol( $args, $this->client ) );
			} catch (BadRequestError $e) {
				$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: failed to add rol to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), json_encode( $e->getInvalidParameters() ) ) );
			} catch (Exception $e) {
				$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: failed to add rol to zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), $e->getMessage() ) );
			}

			break;
		}

		return $rol ?? null;
	}

	/**
	 * Get all available "roltypen" based on the given "zaaktype".
	 *
	 * @since 1.0.0
	 */
	public function get_rol_types(): PagedCollection
	{
		$filter = new RoltypenFilter();
		$filter->add( 'zaaktype', $this->zaaktype_identifier_form_setting( $this->form, $this->supplier_key ) );

		return $this->client->roltypen()->filter( $filter );
	}

	/**
	 * @since 1.0.0
	 */
	public function create_zaak_properties(Zaak $zaak ): void
	{
		$zaak_properties = $this->map_zaak_properties_args();

		foreach ($zaak_properties as $zaak_property) {
			if (empty( $zaak_property['eigenschap'] ) || empty( $zaak_property['waarde'] )) {
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
			} catch (BadRequestError $e) {
				$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: failed to create zaak property for zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), json_encode( $e->getInvalidParameters() ) ) );

			} catch (Exception $e) {
				$this->logger->error( sprintf( 'OWC_GravityForms_ZGW: failed to create zaak property for zaak "%s": %s', $zaak->getValue( 'identificatie', '' ), $e->getMessage() ) );
			}
		}
	}

	/**
	 * Add form field values to arguments for creating "zaak" properties.
	 * Mapping is done by the relation between arguments keys and form fields mappedFieldValueZGWs.
	 *
	 * @since 1.0.0
	 */
	protected function map_zaak_properties_args(): array
	{
		$mappedFields = array();

		foreach ($this->form['fields'] as $field) {
			if (empty( $field->mappedFieldValueZGW ) || strpos( $field->mappedFieldValueZGW, 'https://' ) === false) {
				continue;
			}

			$property_value = rgar( $this->entry, (string) $field->id );

			if (empty( $property_value )) {
				continue;
			}

			if ('date' === $field->type) {
				$property_value = $this->handle_zaak_date_property( $property_value );
			}

			$mappedFields[ $field->id ] = array(
				'eigenschap' => $field->mappedFieldValueZGW,
				'waarde'     => $property_value,
			);
		}

		return $mappedFields;
	}

	/**
	 * @since 1.0.0
	 */
	private function handle_zaak_date_property(string $property_value ): string
	{
		try {
			$property_value = ( new DateTime( $property_value ) )->format( 'Y-m-d' );
		} catch (Exception $e) {
			$property_value = '0000-00-00';
		}

		return $property_value;
	}

	/**
	 * Store generated "zaak" information in the entry's metadata.
	 * Could be used for further processing or debugging.
	 *
	 * @since 1.0.0
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
