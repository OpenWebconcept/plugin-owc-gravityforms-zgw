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

			if (index === 'fileupload') {
				fieldSettings[index] += ', .zgw_upload_setting';
			}
		});

		jQuery(document).on('gform_load_field_settings', function (event, field, form) {
			const mappedFieldZGW = document.getElementById('mappedFieldZGW');
			if(mappedFieldZGW) mappedFieldZGW.value = field['mappedFieldValueZGW'] ?? '';

			const mappedFieldDocumentTypeZGW = document.getElementById('mappedFieldDocumentTypeZGW');
			if(mappedFieldDocumentTypeZGW) mappedFieldDocumentTypeZGW.value = field['mappedFieldDocumentTypeValueZGW'] ?? '';

			const uploadFieldDescriptionZGW = document.getElementById('uploadFieldDescriptionZGW');
			if(uploadFieldDescriptionZGW) uploadFieldDescriptionZGW.value = field['uploadFieldDescriptionValueZGW'] ?? '';

			const linkedFieldKvKBranchNumber = document.getElementById('linkedFieldKvKBranchNumber');
			if (linkedFieldKvKBranchNumber) {
				linkedFieldKvKBranchNumber.checked = String(field['linkedFieldValueKvKBranchNumber'] ?? '0') === '1';
			}
		});
	});
</script>
