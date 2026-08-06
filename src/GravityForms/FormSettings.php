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

use Exception;
use GFAPI;
use OWC\ZGW\Contracts\Client;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;
use function OWC\ZGW\apiClient;

/**
 * Form settings.
 */
class FormSettings
{
	protected string $prefix = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;

	/**
	 * Hooked to admin_init so it fires before GF loads the form for the settings page.
	 * Updating the DB here means GF reads the corrected zaaktype URL when it renders the page.
	 *
	 * @since 1.10.0
	 */
	public function sync_zaaktypen_on_settings_page(): void
	{
		if ( 'gf_edit_forms' !== ( $_GET['page'] ?? '' ) ) {
			return;
		}

		if ( 'settings' !== ( $_GET['view'] ?? '' ) ) {
			return;
		}

		$form_id = absint( $_GET['id'] ?? 0 );
		if ( $form_id <= 0 ) {
			return;
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) ) {
			return;
		}

		$this->sync_form_after_zaaktypen_update( $form );
	}

	/**
	 * Triggers a zaaktypen fetch for the active supplier, which updates stale zaaktype URLs across all
	 * form settings as a side effect. Re-reads the current form from the DB so this page load reflects
	 * any URL that was just updated.
	 *
	 * @since 1.10.0
	 */
	private function sync_form_after_zaaktypen_update( array $form ): void
	{
		if ( '1' === ( $form[ "{$this->prefix}-form-setting-supplier-manually" ] ?? '0' ) ) {
			return;
		}

		$supplier_setting = (string) ( $form[ "{$this->prefix}-form-setting-supplier" ] ?? '' );

		if ( '' === $supplier_setting || 'none' === $supplier_setting ) {
			return;
		}

		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		$supplier_name = '';
		foreach ( $clients as $client ) {
			if ( strtolower( $client['name'] ) === $supplier_setting ) {
				$supplier_name = $client['name'];
				break;
			}
		}

		if ( '' === $supplier_name ) {
			return;
		}

		try {
			( new ZaaktypenAdapter( apiClient( $supplier_name ), $supplier_name ) )->handle();
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Enqueues the script that live-refreshes the "product" select whenever the "zaaktype"
	 * select changes, so a page reload is not required to pick the matching products.
	 *
	 * @since NEXT
	 */
	public function enqueue_settings_scripts(): void
	{
		if ( 'gf_edit_forms' !== ( $_GET['page'] ?? '' ) || 'settings' !== ( $_GET['view'] ?? '' ) ) {
			return;
		}

		$rel         = 'assets/zaaktype-product-select.js';
		$script_path = OWC_GRAVITYFORMS_ZGW_DIR_PATH . $rel;
		$script_url  = OWC_GRAVITYFORMS_ZGW_PLUGIN_URL . $rel;

		wp_enqueue_script(
			'owc-gravityforms-zgw-zaaktype-product-select',
			$script_url,
			array(),
			file_exists( $script_path ) ? filemtime( $script_path ) : null,
			true
		);

		wp_localize_script(
			'owc-gravityforms-zgw-zaaktype-product-select',
			'owcZGWProductSelect',
			array(
				'url'            => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'owcgfzgw_get_producten' ),
				'selectProduct'  => esc_html__( 'Selecteer een product', 'owc-gravityforms-zgw' ),
				'selectZaaktype' => esc_html__( 'Selecteer eerst een zaaktype', 'owc-gravityforms-zgw' ),
				'noProducts'     => esc_html__( 'Geen producten geconfigureerd op dit zaaktype', 'owc-gravityforms-zgw' ),
				'requestError'   => esc_html__( 'Producten konden niet worden opgehaald.', 'owc-gravityforms-zgw' ),
			)
		);
	}

	public function add_form_settings( array $fields, array $form ): array
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

	protected function prepare_supplier_choice( string $label, string $value ): array
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
	protected function get_suppliers_form_settings_fields( array $form, array $fields ): array
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
	protected function handle_suppliers_form_settings_fields( array $form ): array
	{
		$fields = array();

		$clients = (array) get_option( 'zgw_api_settings' );
		$clients = $clients['zgw-api-configured-clients'] ?? array();

		foreach ( $clients as $client ) {
			$supplier_name = $client['name'];
			$supplier_key  = strtolower( $supplier_name );

			if ( $this->supplier_is_selected_in_form_settings( $form, $supplier_key ) ) {
				$fields = $this->prepare_supplier_configuration_fields( $fields, $supplier_name, $supplier_key, $form );
			}
		}

		return $fields;
	}

	/**
	 * Check if a supplier is selected in the form settings.
	 */
	private function supplier_is_selected_in_form_settings( array $form, string $supplier ): bool
	{
		$supplier_form_setting = (string) ( $form[ "{$this->prefix}-form-setting-supplier" ] ?? '' );

		return $supplier_form_setting === $supplier;
	}

	/**
	 * @since NEXT Added the $form parameter, needed to resolve the currently selected zaaktype.
	 */
	protected function prepare_supplier_configuration_fields( array $fields, string $supplier_name, string $supplier_key, array $form ): array
	{
		$api_client          = apiClient( $supplier_name );
		$zaaktype_identifier = $this->resolve_zaaktype_identifier_for_product_choices( $form, $supplier_name, $supplier_key );
		$supplier_dependency = array(
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
		);

		$fields[ $supplier_key ] = array(
			'select_setting' => array(
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-identifier",
					'type'       => 'select',
					'class'      => 'owc-gf-zgw-zaaktype-select',
					'label'      => esc_html__( 'Zaaktype', 'owc-gravityforms-zgw' ),
					'tooltip'    => esc_html__( 'Een zaaktype wordt gebruikt om de aan te maken zaak te definiëren, inclusief de structuur, benodigde gegevens en het verloop van het proces.', 'owc-gravityforms-zgw' ),
					'dependency' => $supplier_dependency,
					'choices'    => $this->format_choices_zaaktypen( $api_client, $supplier_name ),
				),
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-product",
					'type'       => 'select',
					'class'      => 'owc-gf-zgw-product-select',
					'label'      => esc_html__( 'Product', 'owc-gravityforms-zgw' ),
					'tooltip'    => '<h6>' . __( 'Product', 'owc-gravityforms-zgw' ) . '</h6>' . __( 'Kies het product of de dienst dat wordt meegestuurd bij het aanmaken van de zaak. De keuzes zijn afhankelijk van het hierboven gekozen zaaktype.', 'owc-gravityforms-zgw' ),
					'dependency' => $supplier_dependency,
					'choices'    => $this->format_choices_producten( $supplier_name, $zaaktype_identifier ),
				),
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-information-object-type",
					'type'       => 'select',
					'label'      => esc_html__( 'Informatie object type', 'owc-gravityforms-zgw' ),
					'tooltip'    => esc_html__( 'Selecteer het informatieobjecttype dat wordt gebruikt om de PDF van de inzending aan te maken.', 'owc-gravityforms-zgw' ),
					'dependency' => $supplier_dependency,
					'choices'    => $this->format_choices_information_object_types( $api_client, $supplier_name ),
				),
			),
			'manual_setting' => array(
				array(
					'name'       => "{$this->prefix}-form-setting-{$supplier_key}-identifier-manual",
					'type'       => 'text',
					'label'      => esc_html__( 'Zaaktype', 'owc-gravityforms-zgw' ),
					'tooltip'    => esc_html__( 'Een zaaktype wordt gebruikt om de aan te maken zaak te definiëren, inclusief de structuur, benodigde gegevens en het verloop van het proces.', 'owc-gravityforms-zgw' ),
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
					'tooltip'    => esc_html__( 'Selecteer het informatieobjecttype dat wordt gebruikt om de PDF van de inzending aan te maken.', 'owc-gravityforms-zgw' ),
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

	/**
	 * @since 1.9.0
	 */
	private function format_choices_zaaktypen( Client $api_client, string $supplier_name ): array
	{
		return array_merge(
			array(
				array(
					'label' => esc_html__( 'Selecteer een zaaktype', 'owc-gravityforms-zgw' ),
					'value' => '',
				),
			),
			( new ZaaktypenAdapter( $api_client, $supplier_name ) )->handle()
		);
	}

	/**
	 * @since 1.9.0
	 */
	private function format_choices_information_object_types( Client $api_client, string $supplier_name ): array
	{
		return array_merge(
			array(
				array(
					'label' => esc_html__( 'Selecteer een informatie object type', 'owc-gravityforms-zgw' ),
					'value' => '',
				),
			),
			( new InformatieobjecttypeAdapter( $api_client, $supplier_name ) )->handle()
		);
	}

	/**
	 * Resolves the zaaktype identifier the "product" choices should be based on. Gravity Forms
	 * rebuilds the settings fields (via the `gform_form_settings_fields` filter) from the form
	 * as it exists in the database *before* this save, even while processing the save postback
	 * itself, and then validates the posted values against those same choices. So if we based
	 * the product choices on $form alone, saving a newly picked zaaktype together with one of
	 * its products would validate the product against the *previous* zaaktype's product list
	 * and fail with GF's "Invalid selection" error. Preferring the posted identifier (when
	 * present) keeps the choices in sync with what the user actually just selected.
	 *
	 * @since NEXT
	 */
	private function resolve_zaaktype_identifier_for_product_choices( array $form, string $supplier_name, string $supplier_key ): string
	{
		// Gravity Forms' settings framework prefixes every posted field name with
		// "_gform_setting_" (see Settings::$input_name_prefix), so the raw field name never
		// appears as-is in $_POST.
		$posted_field = '_gform_setting_' . "{$this->prefix}-form-setting-{$supplier_key}-identifier";

		if ( array_key_exists( $posted_field, $_POST ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $posted_field ] ) );
		}

		return FormUtils::zaaktype_identifier_form_setting( $form, $supplier_name );
	}

	/**
	 * Choices for the "product" select, based on the "productenOfDiensten" cached for the
	 * currently selected zaaktype (see ZaaktypenAdapter::get_cached_products()). Used for the
	 * initial page render; a live AJAX refresh (see ProductChoicesController) updates these
	 * choices client-side when the zaaktype changes.
	 *
	 * @since NEXT
	 */
	private function format_choices_producten( string $supplier_name, string $zaaktype_identifier ): array
	{
		if ( '' === $zaaktype_identifier ) {
			return array(
				array(
					'label' => esc_html__( 'Selecteer eerst een zaaktype', 'owc-gravityforms-zgw' ),
					'value' => '',
				),
			);
		}

		$producten = ZaaktypenAdapter::get_cached_products( $supplier_name, $zaaktype_identifier );

		$choices = array(
			array(
				'label' => array() === $producten
					? esc_html__( 'Geen producten geconfigureerd op dit zaaktype', 'owc-gravityforms-zgw' )
					: esc_html__( 'Selecteer een product', 'owc-gravityforms-zgw' ),
				'value' => '',
			),
		);

		foreach ( $producten as $product ) {
			$choices[] = array(
				'label' => $product,
				'value' => $product,
			);
		}

		return $choices;
	}
}
