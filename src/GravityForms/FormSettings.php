<?php

declare(strict_types=1);

/**
 * Form settings.
 *
 * @package OWC_GravityForms_ZGW
 *
 * @author  Yard | Digital Agency
 *
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;
use function OWC\ZGW\apiClient;

/**
 * Form settings.
 *
 * @package OWC_GravityForms_ZGW
 *
 * @author  Yard | Digital Agency
 *
 * @since   1.0.0
 */
class FormSettings
{
	protected string $prefix = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;

	/**
	 * @since 1.0.0
	 */
	public function add_form_settings(array $fields, array $form ): array
	{
		$fields['owc-gravityforms-zaaksysteem'] = array(
			'title'       => esc_html__( 'Zaaksysteem', 'owc-gravityforms-zgw' ),
			'description' => esc_html__( 'Om de snelheid te verhogen worden de instellingen van leveranciers pas opgehaald na het kiezen van een leverancier. Dit betekent dat de pagina herladen moet worden na het selecteren van een leverancier.', 'owc-gravityforms-zgw' ),
			'fields'      => array(
				array(
					'name'          => "{$this->prefix}-form-setting-supplier",
					'default_value' => "{$this->prefix}-form-setting-supplier-none",
					'tooltip'       => '<h6>' . __( 'Selecteer een leverancier', 'owc-gravityforms-zgw' ) . '</h6>' . __( 'Kies een Zaaksysteem leverancier. Let op dat je ook de instellingen van de leverancier moet configureren in de hoofdinstellingen van Gravity Forms.', 'owc-gravityforms-zgw' ),
					'type'          => 'select',
					'label'         => esc_html__( 'Selecteer een leverancier', 'owc-gravityforms-zgw' ),
					'choices'       => $this->handle_supplier_choices(),
				),
				array(
					'name'          => "{$this->prefix}-form-setting-supplier-manually",
					'default_value' => '1',
					'tooltip'       => '<h6>' . __( 'Leverancier instellingen', 'owc-gravityforms-zgw' ) . '</h6>' . __( 'Kies hoe de leverancier instellingen geconfigureerd moeten worden.', 'owc-gravityforms-zgw' ),
					'type'          => 'radio',
					'label'         => esc_html__( 'Leverancier instellingen', 'owc-gravityforms-zgw' ),
					'choices'       => array(
						array(
							'name'  => "{$this->prefix}-form-setting-supplier-manually-disabled",
							'label' => __( 'Selecteer instellingen (opgehaald vanuit zaaksysteem)', 'owc-gravityforms-zgw' ),
							'value' => '0',
						),
						array(
							'name'  => "{$this->prefix}-form-setting-supplier-manually-enabled",
							'label' => __( 'Configureer instellingen handmatig (invoeren van URL\'s)', 'owc-gravityforms-zgw' ),
							'value' => '1',
						),
					),
				),
			),
		);

		$fields['owc-gravityforms-zaaksysteem']['fields'] = $this->get_suppliers_form_settings_fields( $form, $fields['owc-gravityforms-zaaksysteem']['fields'] );

		return $fields;
	}

	protected function handle_supplier_choices(): array
	{
		$supplier_choices   = array();
		$supplier_choices[] = $this->prepare_supplier_choice( 'Selecteer leverancier', 'none' );

		if (ContainerResolver::make()->get( 'oz.enabled' )) {
			$supplier_choices[] = $this->prepare_supplier_choice( 'OpenZaak', 'openzaak' );
		}

		if (ContainerResolver::make()->get( 'dj.enabled' )) {
			$supplier_choices[] = $this->prepare_supplier_choice( 'Decos Join', 'decos-join' );
		}

		if (ContainerResolver::make()->get( 'rx.enabled' )) {
			$supplier_choices[] = $this->prepare_supplier_choice( 'Rx.Mission', 'rx-mission' );
		}

		if (ContainerResolver::make()->get( 'xxllnc.enabled' )) {
			$supplier_choices[] = $this->prepare_supplier_choice( 'Xxllnc', 'xxllnc' );
		}

		if (ContainerResolver::make()->get( 'procura.enabled' )) {
			$supplier_choices[] = $this->prepare_supplier_choice( 'Procura', 'procura' );
		}

		return $supplier_choices;
	}

	protected function prepare_supplier_choice(string $label, string $value ): array
	{
		return array(
			'name'  => "{$this->prefix}-form-setting-supplier-{$value}",
			'label' => $label,
			'value' => $value,
		);
	}

	/**
	 * Retrieves the fields associated with a specific supplier based on the form settings and merge with existing fields.
	 *
	 * @since 1.0.0
	 */
	protected function get_suppliers_form_settings_fields(array $form, array $fields ): array
	{
		$supplier_setting = $form[ "{$this->prefix}-form-setting-supplier" ] ?? '';
		$manual           = $form[ "{$this->prefix}-form-setting-supplier-manually" ] ?? '0';
		$suppliers_fields = $this->handle_suppliers_form_settings_fields( $form );

		if (empty( $supplier_setting ) || empty( $suppliers_fields[ $supplier_setting ][ $manual ? 'manual_setting' : 'select_setting' ] )) {
			return $fields;
		}

		return array_merge( $fields, $suppliers_fields[ $supplier_setting ][ $manual ? 'manual_setting' : 'select_setting' ] );
	}

	/**
	 * Fields associated with suppliers, used for matching the fields of the selected supplier in form settings.
	 * This approach minimizes unnecessary requests to multiple sources that are not needed. Because only one supplier can be selected.
	 *
	 * @since 1.0.0
	 */
	protected function handle_suppliers_form_settings_fields(array $form ): array
	{
		$fields = array();

		if (ContainerResolver::make()->get( 'oz.enabled' ) && $this->supplier_is_selected_in_form_settings( $form, 'openzaak' )) {
			$fields = $this->prepare_supplier_configuration_fields( $fields, 'OpenZaak', 'openzaak' );
		}

		if (ContainerResolver::make()->get( 'rx.enabled' ) && $this->supplier_is_selected_in_form_settings( $form, 'rx-mission' )) {
			$fields = $this->prepare_supplier_configuration_fields( $fields, 'RxMission', 'rx-mission' );
		}

		if (ContainerResolver::make()->get( 'xxllnc.enabled' ) && $this->supplier_is_selected_in_form_settings( $form, 'xxllnc' )) {
			$fields = $this->prepare_supplier_configuration_fields( $fields, 'XXLLNC', 'xxllnc' );
		}

		if (ContainerResolver::make()->get( 'procura.enabled' ) && $this->supplier_is_selected_in_form_settings( $form, 'procura' )) {
			$fields = $this->prepare_supplier_configuration_fields( $fields, 'Procura', 'procura' );
		}

		if (ContainerResolver::make()->get( 'dj.enabled' ) && $this->supplier_is_selected_in_form_settings( $form, 'decos-join' )) {
			$fields = $this->prepare_supplier_configuration_fields( $fields, 'DecosJoin', 'decos-join' );
		}

		return $fields;
	}

	/**
	 * Check if a supplier is selected in the form settings.
	 *
	 * @since 1.0.0
	 */
	private function supplier_is_selected_in_form_settings(array $form, string $supplier ): bool
	{
		$supplier_form_setting = (string) ( $form[ "{$this->prefix}-form-setting-supplier" ] ?? '' );

		return $supplier_form_setting === $supplier ? true : false;
	}

	protected function prepare_supplier_configuration_fields(array $fields, string $supplier_name, string $supplier_key ): array
	{
		$fields[ $supplier_key ] = array(
			'select_setting' => array(
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-identifier",
					'type'       => 'select',
					'label'      => esc_html__( 'Zaaktype', 'owc-gravityforms-zgw' ),
					'dependency' => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => "{$this->prefix}-form-setting-supplier",
								'values' => array( $supplier_key ),
							),
							array(
								'field'  => "{$this->prefix}-form-setting-supplier-manually",
								'values' => array( '0' ),
							),
						),
					),
					'choices'    => ( new ZaaktypenAdapter( apiClient( $supplier_name ) ) )->handle(),
				),
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-information-object-type",
					'type'       => 'select',
					'label'      => esc_html__( 'Informatie object type', 'owc-gravityforms-zgw' ),
					'dependency' => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => "{$this->prefix}-form-setting-supplier",
								'values' => array( 'openzaak' ),
							),
							array(
								'field'  => "{$this->prefix}-form-setting-supplier-manually",
								'values' => array( '0' ),
							),
						),
					),
					'choices'    => ( new InformatieobjecttypeAdapter( apiClient( $supplier_name ) ) )->handle(),
				),
			),
		);

		return $fields;
	}
}
