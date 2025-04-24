<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>

<li class="zgw_upload_setting field_setting">
	<label for="uploadFieldDescriptionZGW" class="section_label">
		<?php esc_html_e( 'Document beschrijving', 'owc-gravityforms-zgw' ); ?>
	</label>
	<input type="text" id="uploadFieldDescriptionZGW" onchange="SetFieldProperty('uploadFieldDescriptionValueZGW', this.value);" />
	<small><?php esc_html_e( 'Gebruik het ID van een veld om de waarde daarvan te gebruiken (e.g. [id]) in de beschrijving van het bestand.', 'owc-gravityforms-zgw' ); ?></small>
</li>
