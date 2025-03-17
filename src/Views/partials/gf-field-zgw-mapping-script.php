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
	jQuery.each(fieldSettings, function(index, value) {
		fieldSettings[index] += ', .zgw_mapping_setting';
	});
	jQuery(document).bind('gform_load_field_settings', function(event, field, form) {
		jQuery('#mappendFieldZGW').val(field['mappedFieldValueZGW']);
		jQuery('#mappedFieldDocumentTypeZGW').val(field['mappedFieldDocumentTypeValueZGW']);
	});
</script>
