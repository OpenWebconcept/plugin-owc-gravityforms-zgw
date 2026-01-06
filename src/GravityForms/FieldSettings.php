<?php
/**
 * Field settings.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use GFAPI;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormUtils;
use OWC\ZGW\Endpoints\Filter\EigenschappenFilter;
use OWC\ZGW\Entities\Attributes\Confidentiality;
use OWC\ZGW\Entities\Informatieobjecttype;
use OWC\ZGW\Entities\Zaaktype;
use OWC\ZGW\Support\Collection;
use function OWC\ZGW\apiClient;

/**
 * Field settings.
 *
 * @since 1.0.0
 */
class FieldSettings
{
	protected const TRANSIENT_LIFETIME_IN_SECONDS = 64800; // 18 hours.

	/**
	 * Add a select element to form fields inside the editor used for field mapping between
	 * form field and ZGW properties.
	 */
	public function add_select($position, $form_id ): void
	{
		if ( ! class_exists( 'GFAPI' ) || 0 !== $position ) {
			return;
		}

		$form = GFAPI::get_form( $form_id );

		if ( false === $form ) {
			return;
		}

		$supplier_config = FormUtils::get_supplier_config( $form );

		if ( empty( $supplier_config['name'] ) || empty( $supplier_config['client_type'] ) ) {
			return;
		}

		$zaak_type_identifier = FormUtils::zaaktype_identifier_form_setting( $form, $supplier_config['name'] );
		$zaak_type            = $this->get_zaak_type( $supplier_config['name'], $zaak_type_identifier );

		if ( ! $zaak_type ) {
			return;
		}

		$properties = empty( $zaak_type->url ) ? array() : $this->get_zaak_type_properties( $supplier_config['name'], $zaak_type->url );

		owc_gravityforms_zgw_render_view(
			'partials/gf-field-zgw-mapping-options',
			array(
				'properties'  => $properties instanceof Collection ? $this->prepare_properties_options( $properties ) : array(),
				'objecttypes' => $this->prepare_object_types_options( $this->get_information_object_types( $zaak_type, $zaak_type->identificatie ) ),
			)
		);
		owc_gravityforms_zgw_render_view( 'partials/gf-field-zgw-upload-field-options' );
	}

	/**
	 * Enqueues a script that updates the field property based on the selected value
	 * from the field mapping select element.
	 *
	 * This script ensures that the chosen value in the field mapping dropdown
	 * is properly assigned to the corresponding field property.
	 */
	public function add_select_script(): void
	{
		owc_gravityforms_zgw_render_view( 'partials/gf-field-zgw-mapping-script' );
	}

	/**
	 * Use the selected "zaaktype identifier" to retrieve the "zaaktype".
	 *
	 * @todo we cannot use the zaaktype URI to retrieve a zaaktype because it is bound to change when the zaaktype is updated. There doesn't seem to be a way to retrieve the zaaktype by identifier, so we have to get all the zaaktypen first and then filter them by identifier. We should change this when the API supports this.
	 *
	 * @see https://github.com/OpenWebconcept/plugin-owc-gravityforms-zaaksysteem/issues/13#issue-1697256063
	 */
	public function get_zaak_type(string $supplier_name, string $zaak_type_identifier ): ?Zaaktype
	{
		$transient_key = sprintf( '%s-%s', sanitize_title( $supplier_name ), sanitize_title( $zaak_type_identifier ) );
		$zaak_type     = get_transient( $transient_key );

		if ( $zaak_type instanceof Zaaktype ) {
			return $zaak_type;
		}

		$client = apiClient( $supplier_name );

		try {
			$zaak_type = $this->get_zaak_type_by_client( $client, $zaak_type_identifier );
		} catch ( Exception $e ) {
			$zaak_type = null;
		}

		if ( ! $zaak_type instanceof Zaaktype ) {
			return null;
		}

		set_transient( $transient_key, $zaak_type, self::TRANSIENT_LIFETIME_IN_SECONDS );

		return $zaak_type;
	}

	/**
	 * Decos API is very slow.
	 * For demostration purposes we match on "zaaktype" identifier to ensure some speed.
	 */
	protected function get_zaak_type_by_client($client, string $zaak_type_identifier ): ?Zaaktype
	{
		/**
		 * In previous versions the UUID of a "zaaktype" was saved instead of its URL.
		 * This check takes the last part of the URL, the identifier, and is here to support backwards compatibility.
		 */
		if ( filter_var( $zaak_type_identifier, FILTER_VALIDATE_URL ) ) {
			$explode              = explode( '/', $zaak_type_identifier ) ? explode( '/', $zaak_type_identifier ) : array();
			$zaak_type_identifier = end( $explode );
		}

		if ( '' === $zaak_type_identifier ) {
			return null;
		}

		$zaak_type = $client->zaaktypen()->get( $zaak_type_identifier );

		/**
		 * When the API supports filtering on zaak type identification, this line should be used.
		 * Fow now the "byIdentifier" method is quite memory-intensive.
		 */
		// $zaak_type = $client->zaaktypen()->byIdentifier($zaak_type_identifier);

		return $zaak_type;
	}

	/**
	 * Get the "zaakeigenschappen" belonging to the chosen "zaaktype".
	 */
	public function get_zaak_type_properties(string $supplier_name, string $zaak_type_url ): Collection
	{
		$client = apiClient( $supplier_name );

		$filter = ( new EigenschappenFilter() )->add( 'zaaktype', $zaak_type_url );
		$types  = array();
		$page   = 1;

		while ( $page ) {
			try {
				$result = $client->eigenschappen()->filter( $filter->page( $page ) );
				$types  = array_merge( $types, $result->all() );
				$page   = $result->pageMeta()->getNextPageNumber();
			} catch ( Exception $e ) {
				ContainerResolver::make()->get( 'logger.zgw' )->error( $e->getMessage() );

				break;
			}
		}

		// Returns collected results if pagination is successful; if an error occurred during pagination, retrieves non-paginated results as a fallback.
		return count( $types ) ? Collection::collect( $types ) : $client->eigenschappen()->filter( $filter );
	}

	protected function prepare_properties_options(Collection $properties ): array
	{
		$options = $properties->map(
			function ($property ) {
				if ( empty( $property['naam'] ) || empty( $property['url'] ) ) {
					return array();
				}

				return array(
					'label' => $property['naam'],
					'value' => $property['url'],
				);
			}
		)->toArray();

		return array_filter( (array) $options );
	}

	public function get_information_object_types(Zaaktype $zaak_type, string $zaak_type_identification ): array
	{
		$transient_key = sprintf( 'zaaktype-%s-mapping-information-object-types', sanitize_title( $zaak_type_identification ) );
		$types         = get_transient( $transient_key );

		if ( is_array( $types ) && $types ) {
			return $types;
		}

		$types = $zaak_type->informatieobjecttypen ? $zaak_type->informatieobjecttypen->all() : array();

		if ( empty( $types ) ) {
			return array();
		}

		set_transient( $transient_key, $types, self::TRANSIENT_LIFETIME_IN_SECONDS );

		return $types;
	}

	protected function prepare_object_types_options(array $types ): array
	{
		if ( empty( $types ) ) {
			return array();
		}

		return (array) Collection::collect( $types )->map(
			function (Informatieobjecttype $object_type ) {
				$designation = $object_type->vertrouwelijkheidaanduiding;
				if ( $designation instanceof Confidentiality ) {
					$designation = $designation->name ?? '';
				}

				return array(
					'label' => "{$object_type->omschrijving} ({$designation})",
					'value' => $object_type->url,
				);
			}
		)->all();
	}
}
