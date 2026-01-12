<?php

declare(strict_types=1);

/**
 * Form settings.
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

use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;
use function OWC\ZGW\apiClient;

/**
 * Form settings.
 */
class FormSettings
{
	protected string $prefix = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;

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
					'name'    => "{$this->prefix}-form-setting-overwrite-bsn",
					'type'    => 'text',
					'label'   => esc_html__( 'Dummy BSN', 'owc-gravityforms-zgw' ),
					'tooltip' => '<h6>' . __( 'BSN vervangen door dummywaarde (BSN)', 'owc-gravityforms-zgw' ) . '</h6>' . __( 'Vul hier een 9 cijferig BSN in die gebruikt wordt bij het aanmaken van een zaak. Handig wanneer er geen BSN van de burger aanwezig of vereist is.', 'owc-gravityforms-zgw' ),
				),
				array(
					'name'    => "{$this->prefix}-form-setting-overwrite-kvk",
					'type'    => 'text',
					'label'   => esc_html__( 'Dummy KVK', 'owc-gravityforms-zgw' ),
					'tooltip' => '<h6>' . __( 'KVK-nummer vervangen door dummywaarde (KVK)', 'owc-gravityforms-zgw' ) . '</h6>' . __( 'Vul hier een 8 cijferig KVK-nummer in die gebruikt wordt bij het aanmaken van een zaak. Handig wanneer er geen KVK-nummer van een onderneming aanwezig of vereist is.', 'owc-gravityforms-zgw' ),
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
		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		$supplier_choices   = array();
		$supplier_choices[] = $this->prepare_supplier_choice( 'Selecteer leverancier', 'none' );

		foreach ( $clients as $key => $client ) {
			$label              = $client['name'];
			$supplier_choices[] = $this->prepare_supplier_choice( $label, strtolower( $label ) );
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
	 */
	protected function get_suppliers_form_settings_fields(array $form, array $fields ): array
	{
		$supplier_setting = $form[ "{$this->prefix}-form-setting-supplier" ] ?? '';
		$manual           = $form[ "{$this->prefix}-form-setting-supplier-manually" ] ?? '0';
		$suppliers_fields = $this->handle_suppliers_form_settings_fields( $form );

		if ( empty( $supplier_setting ) || empty( $suppliers_fields[ $supplier_setting ][ $manual ? 'manual_setting' : 'select_setting' ] ) ) {
			return $fields;
		}

		return array_merge(
			$fields,
			$suppliers_fields[ $supplier_setting ][ $manual ? 'manual_setting' : 'select_setting' ]
		);
	}

	/**
	 * Fields associated with suppliers, used for matching the fields of the selected supplier in form settings.
	 * This approach minimizes unnecessary requests to multiple sources that are not needed. Because only one supplier can be selected.
	 */
	protected function handle_suppliers_form_settings_fields(array $form ): array
	{
		$fields = array();

		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		foreach ( $clients as $client ) {
			$supplier_name = $client['name'];
			$supplier_key  = strtolower( $supplier_name );

			if ( $this->supplier_is_selected_in_form_settings( $form, $supplier_key ) ) {
				$fields = $this->prepare_supplier_configuration_fields( $fields, $supplier_name, $supplier_key, $client );
			}
		}

		return $fields;
	}

	/**
	 * Check if a supplier is selected in the form settings.
	 */
	private function supplier_is_selected_in_form_settings(array $form, string $supplier ): bool
	{
		$supplier_form_setting = (string) ( $form[ "{$this->prefix}-form-setting-supplier" ] ?? '' );

		return $supplier_form_setting === $supplier;
	}

	protected function prepare_supplier_configuration_fields(array $fields, string $supplier_name, string $supplier_key ): array
	{
		$api_client = apiClient( $supplier_name );

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
					'choices'    => ( new ZaaktypenAdapter( $api_client, $supplier_name ) )->handle(),
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
								'values' => array( $supplier_key ),
							),
							array(
								'field'  => "{$this->prefix}-form-setting-supplier-manually",
								'values' => array( '0' ),
							),
						),
					),
					'choices'    => ( new InformatieobjecttypeAdapter( $api_client, $supplier_name ) )->handle(),
				),
			),
			'manual_setting' => array(
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-identifier-manual",
					'type'       => 'text',
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
								'values' => array( true, '1' ),
							),
						),
					),
				),
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-information-object-type-manual",
					'type'       => 'text',
					'label'      => esc_html__( 'Informatie object type', 'owc-gravityforms-zgw' ),
					'dependency' => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => "{$this->prefix}-form-setting-supplier",
								'values' => array( $supplier_key ),
							),
							array(
								'field'  => "{$this->prefix}-form-setting-supplier-manually",
								'values' => array( true, '1' ),
							),
						),
					),
				),
			),
		);

		return $fields;
	}
}
