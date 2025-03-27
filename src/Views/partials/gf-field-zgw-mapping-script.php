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
<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function () {
		jQuery.each(fieldSettings, function(index, value) {
			fieldSettings[index] += ', .zgw_mapping_setting';
		});

		jQuery(document).on('gform_load_field_settings', function (event, field, form) {
			document.getElementById('mappendFieldZGW').value = field['mappedFieldValueZGW'];
			document.getElementById('mappedFieldDocumentTypeZGW').value = field['mappedFieldDocumentTypeValueZGW'];
		});
	});
</script>
