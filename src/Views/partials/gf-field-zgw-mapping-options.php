<?php

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit();
}

$properties  = $properties ?? array();
$objecttypes = $objecttypes ?? array();
?>

<li class="label_setting field_setting zgw_mapping_setting">
	<label for="mappendFieldZGW" class="section_label">
		<?php _e( 'Zaaksysteem mapping', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappendFieldZGW" onchange="SetFieldProperty('mappedFieldValueZGW', this.value);">
		<option value=""><?php _e( 'Kies veldnaam Zaaksysteem', 'owc-gravityforms-zgw' ); ?></option>
		<option value="bronorganisatie"><?php _e( 'Bronorganisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="zaaktype"><?php _e( 'Zaaktype', 'owc-gravityforms-zgw' ); ?></option>
		<option value="omschrijving"><?php _e( 'Omschrijving', 'owc-gravityforms-zgw' ); ?></option>
		<option value="toelichting"><?php _e( 'Toelichting', 'owc-gravityforms-zgw' ); ?></option>
		<option value="registratiedatum"><?php _e( 'Registratiedatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="verantwoordelijkeOrganisatie"><?php _e( 'Verantwoordelijke organisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="startdatum"><?php _e( 'Startdatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="informatieobject"><?php _e( 'Informatieobject', 'owc-gravityforms-zgw' ); ?></option>
		<optgroup label="Zaakeigenschappen">
		<?php foreach ($properties as $property) : ?>
			<option value="<?php echo $property['value']; ?>">
				<?php echo $property['label']; ?>
			</option>
		<?php endforeach; ?>
	</select>
</li>
<li class="label_setting field_setting zgw_mapping_setting">
	<label for="mappedFieldDocumentTypeZGW" class="section_label">
		<?php _e( 'Document typen', 'owc-gravityforms-zgw' ); ?>
	</label>
	<select id="mappedFieldDocumentTypeZGW" onchange="SetFieldProperty('mappedFieldDocumentTypeValueZGW', this.value);">
		<option value=""><?php _e( 'Kies een document type', 'owc-gravityforms-zgw' ); ?></option>
		<?php foreach ($objecttypes as $property) : ?>
			<option value="<?php echo $property['value']; ?>">
					<?php echo $property['label']; ?>
				</option>
		<?php endforeach; ?>
	</select>
</li>
