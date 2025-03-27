<?php
/**
 * Abstract create submission PDF action.
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

use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingsPDF;
use OWCGravityFormsZGW\Traits\CheckURL;
use OWCGravityFormsZGW\Traits\FormSetting;
use OWCGravityFormsZGW\Traits\InformationObject;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Entities\Enkelvoudiginformatieobject;
use OWC\ZGW\Entities\Zaak;
use OWC\ZGW\Entities\Zaakinformatieobject;
use function OWC\ZGW\apiClient;

/**
 * Abstract create submission PDF action.
 *
 * @since 1.0.0
 */
abstract class AbstractCreateSubmissionPDFAction
{
	use CheckURL;
	use FormSetting;
	use InformationObject;

	protected array $entry;
	protected array $form;
	protected string $supplier_name;
	protected string $supplier_key;
	protected Zaak $zaak;
	protected Client $client;
	protected FormSettingsPDF $pdf_settings;

	public function __construct(array $entry, array $form, string $supplier_name, string $supplier_key, Zaak $zaak )
	{
		$this->entry         = $entry;
		$this->form          = $form;
		$this->supplier_name = $supplier_name;
		$this->supplier_key  = $supplier_key;
		$this->zaak          = $zaak;
		$this->client        = apiClient( $this->supplier_name );
		$this->pdf_settings  = new FormSettingsPDF( $entry, $form );
	}

	abstract public function add_submission_pdf(): ?Zaakinformatieobject;

	/**
	 * @since 1.0.0
	 */
	protected function get_submission_args_pdf(): array
	{
		if ( ! class_exists( 'GPDFAPI' )) {
			return array();
		}

		if ( ! $this->pdf_settings->pdf_form_setting_is_active()) {
			return array();
		}

		$url_pdf = $this->pdf_settings->url_pdf();

		if (empty( $url_pdf )) {
			return array();
		}

		$this->pdf_settings->update_public_access_setting_pdf( 'enable' );

		if ( ! $this->check_url( $url_pdf )) {
			$this->pdf_settings->update_public_access_setting_pdf( 'disable' );

			return array();
		}

		$args = $this->prepare_args_pdf( 'Aanvraag - eFormulier', $url_pdf );

		$this->pdf_settings->update_public_access_setting_pdf( 'disable' );

		return $args;
	}


	/**
	 * @since 1.0.0
	 */
	public function prepare_args_pdf(string $file_name, string $object_url ): array
	{
		$information_object_type = $this->information_object_type_form_setting( $this->form, $this->supplier_key );

		if (empty( $information_object_type )) {
			return array();
		}

		$file_size    = $this->get_content_length( $object_url );
		$file_content = $this->encode_base64_from_url( $object_url );

		$args                                = array();
		$args['titel']                       = $file_name;
		$args['formaat']                     = $this->get_content_type( $object_url );
		$args['bestandsnaam']                = sprintf( '%s.pdf', sanitize_title( $file_name ) );
		$args['bestandsomvang']              = $file_size ? (int) $file_size : strlen( $file_content );
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

	/**
	 * @since 1.0.0
	 */
	public function create_submission_pdf(array $args ): ?Enkelvoudiginformatieobject
	{
		if (empty( $args )) {
			return null;
		}

		$pdf = $this->client->enkelvoudiginformatieobjecten()->create( new Enkelvoudiginformatieobject( $args, $this->client ) );
		$pdf->setValue( 'zaak', $this->zaak->url ); // Required for connecting the "informationobject" to the "zaak".

		return $pdf;
	}

	/**
	 * @since 1.0.0
	 */
	public function connect_pdf_to_zaak(?Enkelvoudiginformatieobject $pdf ): ?Zaakinformatieobject
	{
		if ( ! $pdf instanceof Enkelvoudiginformatieobject) {
			return null;
		}

		return $this->client->zaakinformatieobjecten()->create( new Zaakinformatieobject( $pdf->toArray(), $this->client ) );
	}
}
