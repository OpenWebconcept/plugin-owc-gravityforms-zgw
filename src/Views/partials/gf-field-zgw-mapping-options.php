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

<li class="field_setting zgw_mapping_setting">
	<label for="mappedFieldZGW" class="section_label">
		<?php esc_html_e( 'Zaaksysteem mapping', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappedFieldZGW" onchange="SetFieldProperty('mappedFieldValueZGW', this.value);">
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
<li class="zgw_upload_setting field_setting">
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
<li class="zgw_kvk_setting field_setting">
	<input type="checkbox" id="linkedFieldKvKBranchNumber" name="linkedFieldKvKBranchNumber" onchange="SetFieldProperty('linkedFieldValueKvKBranchNumber', this.checked ? '1' : '0');">
	<label for="linkedFieldKvKBranchNumber" class="section_label">
		<?php esc_html_e( 'KVK vestigingsnummer', 'owc-gravityforms-zgw' ); ?>
	</label>
	<small><?php esc_html_e( 'Kies dit veld om het KVK vestigingsnummer te koppelen aan de rol van de indiener.', 'owc-gravityforms-zgw' ); ?></small>
</li>
