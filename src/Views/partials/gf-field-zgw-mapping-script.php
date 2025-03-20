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
<script type='text/javascript'>
	document.addEventListener('DOMContentLoaded', function() {
		fieldSettings.forEach(function(value, index) {
			fieldSettings[index] += ', .zgw_mapping_setting';
		});

		document.addEventListener('gform_load_field_settings', function(event) {
			const field = event.detail.field;
			document.getElementById('mappendFieldZGW').value = field['mappedFieldValueZGW'];
			document.getElementById('mappedFieldDocumentTypeZGW').value = field['mappedFieldDocumentTypeValueZGW'];
		});
	});
</script>