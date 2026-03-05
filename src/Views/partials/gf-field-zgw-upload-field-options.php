<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
$objecttypes = $objecttypes ?? array();
?>

<li class="zgw_upload_setting field_setting">
	<h3 style="margin-top: 0px;">Bestandsupload</h3>
</li>
<li class="zgw_upload_setting field_setting">
	<label for="mappedFieldDocumentTypeZGW" class="section_label">
		<?php esc_html_e( 'Document type', 'owc-gravityforms-zgw' ); ?>
	</label>
	<select id="mappedFieldDocumentTypeZGW" onchange="SetFieldProperty('mappedFieldDocumentTypeValueZGW', this.value);">
		<option value=""><?php esc_html_e( 'Selecteer een optie', 'owc-gravityforms-zgw' ); ?></option>
		<?php foreach ( $objecttypes as $property ) : ?>
			<option value="<?php echo esc_attr( $property['value'] ); ?>">
				<?php echo esc_html( $property['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</li>
<li class="zgw_upload_setting field_setting">
	<label for="uploadFieldDescriptionZGW" class="section_label">
		<?php esc_html_e( 'Beschrijving', 'owc-gravityforms-zgw' ); ?>
	</label>
	<input type="text" id="uploadFieldDescriptionZGW" onchange="SetFieldProperty('uploadFieldDescriptionValueZGW', this.value);" />
	<small><?php esc_html_e( 'Gebruik het ID van een veld om de waarde daarvan te gebruiken (e.g. [id]) in de beschrijving van het bestand.', 'owc-gravityforms-zgw' ); ?></small>
</li>
