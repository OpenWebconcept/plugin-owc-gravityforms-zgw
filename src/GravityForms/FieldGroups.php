<?php
/**
 * Field groups.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\GravityForms\FieldSettings;

/**
 * Field groups.
 *
 * @since NEXT
 */
class FieldGroups
{
	/**
	 * Adds a custom field group to the form editor.
	 */
	public function field_groups_form_editor(array $field_groups ): array
	{
		$field_groups['owc_gf_zgw'] = array(
			'name'   => 'owc_gf_zgw',
			'label'  => __( 'Zaaksysteem (ZGW)', 'owc-gravityforms-zgw' ),
			'fields' => array(),
		);

		return $field_groups;
	}

	/**
	 * Adds a custom tab to the field settings.
	 */
	public function add_tabs(array $tabs ): array
	{
		$tabs[] = array(
			'id'    => 'owc_gf_zgw',
			'title' => __( 'Zaaksysteem (ZGW)', 'owc-gravityforms-zgw' ),
		);

		return $tabs;
	}

	/**
	 * Adds content to the custom tab in the field settings.
	 */
	public function add_tab_content(array $form ): void
	{
		esc_html( ( new FieldSettings() )->add_select( (int) $form['id'] ) );
	}
}
