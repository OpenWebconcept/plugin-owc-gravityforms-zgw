<?php

declare(strict_types=1);

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

use function OWC\ZGW\apiClient;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\InformatieobjecttypeAdapter;
use OWCGravityFormsZGW\GravityForms\FormSettingAdapters\ZaaktypenAdapter;

class FormSettings
{
    protected string $prefix = OWC_GRAVITYFORMS_ZGW_SETTINGS_PREFIX;

    /**
     * @since 1.0.0
     */
    public function addFormSettings(array $fields, array $form): array
    {
        $fields['owc-gravityforms-zaaksysteem'] = [
            'title' => esc_html__('Zaaksysteem', 'owc-gravityforms-zgw'),
            'description' => esc_html__('Om de snelheid te verhogen worden de instellingen van leveranciers pas opgehaald na het kiezen van een leverancier. Dit betekent dat de pagina herladen moet worden na het selecteren van een leverancier.', 'owc-gravityforms-zgw'),
            'fields' => [
                [
                    'name' => "{$this->prefix}-form-setting-supplier",
                    'default_value' => "{$this->prefix}-form-setting-supplier-none",
                    'tooltip' => '<h6>' . __('Selecteer een leverancier', 'owc-gravityforms-zgw') . '</h6>' . __('Kies een Zaaksysteem leverancier. Let op dat je ook de instellingen van de leverancier moet configureren in de hoofdinstellingen van Gravity Forms.', 'owc-gravityforms-zgw'),
                    'type' => 'select',
                    'label' => esc_html__('Selecteer een leverancier', 'owc-gravityforms-zgw'),
                    'choices' => $this->handleSupplierChoices(),
                ],
                [
                    'name' => "{$this->prefix}-form-setting-supplier-manually",
                    'default_value' => "1",
                    'tooltip' => '<h6>' . __('Leverancier instellingen', 'owc-gravityforms-zgw') . '</h6>' . __('Kies hoe de leverancier instellingen geconfigureerd moeten worden.', 'owc-gravityforms-zgw'),
                    'type' => 'radio',
                    'label' => esc_html__('Leverancier instellingen', 'owc-gravityforms-zgw'),
                    'choices' => [
                        [
                            'name' => "{$this->prefix}-form-setting-supplier-manually-disabled",
                            'label' => __('Selecteer instellingen (opgehaald vanuit zaaksysteem)', 'owc-gravityforms-zgw'),
                            'value' => '0',
                        ],
                        [
                            'name' => "{$this->prefix}-form-setting-supplier-manually-enabled",
                            'label' => __('Configureer instellingen handmatig (invoeren van URL\'s)', 'owc-gravityforms-zgw'),
                            'value' => '1',
                        ],
                    ],
                ],
            ],
        ];

        $fields['owc-gravityforms-zaaksysteem']['fields'] = $this->getSuppliersFormSettingsFields($form, $fields['owc-gravityforms-zaaksysteem']['fields']);

        return $fields;
    }

    protected function handleSupplierChoices(): array
    {
        $supplierChoices = [];
        $supplierChoices[] = $this->prepareSupplierChoice('Selecteer leverancier', 'none');

        if (ContainerResolver::make()->get('oz.enabled')) {
            $supplierChoices[] = $this->prepareSupplierChoice('OpenZaak', 'openzaak');
        }

        if (ContainerResolver::make()->get('dj.enabled')) {
            $supplierChoices[] = $this->prepareSupplierChoice('Decos Join', 'decos-join');
        }

        if (ContainerResolver::make()->get('rx.enabled')) {
            $supplierChoices[] = $this->prepareSupplierChoice('Rx.Mission', 'rx-mission');
        }

        if (ContainerResolver::make()->get('xxllnc.enabled')) {
            $supplierChoices[] = $this->prepareSupplierChoice('Xxllnc', 'xxllnc');
        }

        if (ContainerResolver::make()->get('procura.enabled')) {
            $supplierChoices[] = $this->prepareSupplierChoice('Procura', 'procura');
        }

        return $supplierChoices;
    }

    protected function prepareSupplierChoice(string $label, string $value): array
    {
        return [
            'name' => "{$this->prefix}-form-setting-supplier-{$value}",
            'label' => $label,
            'value' => $value,
        ];
    }

    /**
     * Retrieves the fields associated with a specific supplier based on the form settings and merge with existing fields.
     *
     * @since 1.0.0
     */
    protected function getSuppliersFormSettingsFields(array $form, array $fields): array
    {
        $supplierSetting = $form[ "{$this->prefix}-form-setting-supplier" ] ?? '';
        $manual = $form[ "{$this->prefix}-form-setting-supplier-manually" ] ?? '0';
        $suppliersFields = $this->handleSuppliersFormSettingsFields();

        if (empty($supplierSetting) || empty($suppliersFields[ $supplierSetting ][ $manual ? 'manual_setting' : 'select_setting' ])) {
            return $fields;
        }

        return array_merge($fields, $suppliersFields[ $supplierSetting ][ $manual ? 'manual_setting' : 'select_setting' ]);
    }

    /**
     * Fields associated with suppliers, used for matching the fields of the selected supplier in form settings.
     * This approach minimizes unnecessary requests to multiple sources that are not needed. Because only one supplier can be selected.
     *
     * @since 1.0.0
     */
    protected function handleSuppliersFormSettingsFields(): array
    {
        $fields = [];

        if (ContainerResolver::make()->get('oz.enabled')) {
            $fields = $this->prepareSupplierConfigurationFields($fields, 'OpenZaak', 'openzaak');
        }

        if (ContainerResolver::make()->get('rx.enabled')) {
            $fields = $this->prepareSupplierConfigurationFields($fields, 'RxMission', 'rx-mission');
        }

        if (ContainerResolver::make()->get('xxllnc.enabled')) {
            $fields = $this->prepareSupplierConfigurationFields($fields, 'XXLNC', 'xxllnc');
        }

        if (ContainerResolver::make()->get('procura.enabled')) {
            $fields = $this->prepareSupplierConfigurationFields($fields, 'Procura', 'procura');
        }

        if (ContainerResolver::make()->get('dj.enabled')) {
            $fields = $this->prepareSupplierConfigurationFields($fields, 'DecosJoin', 'decos-join');
        }

        return $fields;
    }

    protected function prepareSupplierConfigurationFields(array $fields, string $supplierName, string $supplierKey): array
    {
        $fields[$supplierKey] = [
            'select_setting' => [
                [
                    'name' => "{$this->prefix}-form-setting-{$supplierKey}-identifier",
                    'type' => 'select',
                    'label' => esc_html__('Zaaktype', 'owc-gravityforms-zgw'),
                    'dependency' => [
                        'live' => true,
                        'fields' => [
                            [
                                'field' => "{$this->prefix}-form-setting-supplier",
                                'values' => [ $supplierKey ],
                            ],
                            [
                                'field' => "{$this->prefix}-form-setting-supplier-manually",
                                'values' => [ '0' ],
                            ],
                        ],
                    ],
                    'choices' => (new ZaaktypenAdapter(apiClient($supplierName)))->handle(),
                ],
                [
                    'name' => "{$this->prefix}-form-setting-{$supplierKey}-information-object-type",
                    'type' => 'select',
                    'label' => esc_html__('Informatie object type', 'owc-gravityforms-zgw'),
                    'dependency' => [
                        'live' => true,
                        'fields' => [
                            [
                                'field' => "{$this->prefix}-form-setting-supplier",
                                'values' => [ 'openzaak' ],
                            ],
                            [
                                'field' => "{$this->prefix}-form-setting-supplier-manually",
                                'values' => [ '0' ],
                            ],
                        ],
                    ],
                    'choices' => (new InformatieobjecttypeAdapter(apiClient($supplierName)))->handle(),
                ],
            ],
        ];

        return $fields;
    }
}
