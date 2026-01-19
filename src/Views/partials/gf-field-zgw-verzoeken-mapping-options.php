<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$properties  = $properties ?? array();
$objecttypes = $objecttypes ?? array();
?>

<li class="label_setting field_setting zgw_mapping_setting">
	<label for="mappedFieldZGW" class="section_label">
		<?php esc_html_e( 'ZGW verzoek mapping', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappedFieldZGW" onchange="SetFieldProperty('mappedFieldValueZGW', this.value);">
		<option value=""><?php esc_html_e( 'Kies veldnaam ZGW verzoek', 'owc-gravityforms-zgw' ); ?></option>
		<option value="startAt"><?php esc_html_e( 'Startdatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="naam"><?php esc_html_e( 'Naam', 'owc-gravityforms-zgw' ); ?></option>
		<option value="omschrijving"><?php esc_html_e( 'Omschrijving', 'owc-gravityforms-zgw' ); ?></option>
		<option value="telefoonnummer"><?php esc_html_e( 'Telefoonnummer', 'owc-gravityforms-zgw' ); ?></option>
		<option value="informatieobject"><?php esc_html_e( 'Informatieobject', 'owc-gravityforms-zgw' ); ?></option>
	</select>
</li>
<li class="label_setting field_setting zgw_mapping_setting">
	<label for="mappedFieldDocumentTypeZGW" class="section_label">
		<?php esc_html_e( 'Document typen', 'owc-gravityforms-zgw' ); ?>
	</label>
	<select id="mappedFieldDocumentTypeZGW" onchange="SetFieldProperty('mappedFieldDocumentTypeValueZGW', this.value);">
		<option value=""><?php esc_html_e( 'Kies een document type', 'owc-gravityforms-zgw' ); ?></option>
		<?php foreach ( $objecttypes as $property ) : ?>
			<option value="<?php echo esc_attr( $property['value'] ); ?>">
				<?php echo esc_html( $property['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</li>
