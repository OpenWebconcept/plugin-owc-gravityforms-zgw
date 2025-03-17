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
	<label for="mappendFieldZGW" class="section_label">
		<?php esc_html_e( 'Zaaksysteem mapping', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappendFieldZGW" onchange="SetFieldProperty('mappedFieldValueZGW', this.value);">
		<option value=""><?php esc_html_e( 'Kies veldnaam Zaaksysteem', 'owc-gravityforms-zgw' ); ?></option>
		<option value="bronorganisatie"><?php esc_html_e( 'Bronorganisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="zaaktype"><?php esc_html_e( 'Zaaktype', 'owc-gravityforms-zgw' ); ?></option>
		<option value="omschrijving"><?php esc_html_e( 'Omschrijving', 'owc-gravityforms-zgw' ); ?></option>
		<option value="toelichting"><?php esc_html_e( 'Toelichting', 'owc-gravityforms-zgw' ); ?></option>
		<option value="registratiedatum"><?php esc_html_e( 'Registratiedatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="verantwoordelijkeOrganisatie"><?php esc_html_e( 'Verantwoordelijke organisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="startdatum"><?php esc_html_e( 'Startdatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="informatieobject"><?php esc_html_e( 'Informatieobject', 'owc-gravityforms-zgw' ); ?></option>
		<optgroup label="Zaakeigenschappen">
			<?php foreach ( $properties as $property ) : ?>
				<option value="<?php echo esc_attr( $property['value'] ); ?>">
					<?php echo esc_html( $property['label'] ); ?>
				</option>
			<?php endforeach; ?>
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