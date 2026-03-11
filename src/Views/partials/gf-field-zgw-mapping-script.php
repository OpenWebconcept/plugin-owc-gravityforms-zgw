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

			if (index === 'text') {
				fieldSettings[index] += ', .zgw_kvk_setting, .zgw_involved_identification_mapping_setting, .zgw_contact_person_role_mapping_setting';
			}

			if (index === 'date') {
				fieldSettings[index] += ', .zgw_involved_identification_mapping_setting';
			}

			if (index === 'email') {
				fieldSettings[index] += ', .zgw_involved_identification_mapping_setting, .zgw_contact_person_role_mapping_setting';
			}

			if (index === 'phone') {
				fieldSettings[index] += ', .zgw_involved_identification_mapping_setting, .zgw_contact_person_role_mapping_setting';
			}
		});

		jQuery(document).on('gform_load_field_settings', function (event, field, form) {
			const mappedFieldZGW = document.getElementById('mappedFieldZGW');
			if (mappedFieldZGW) {
				const value = field['mappedFieldValueZGW'] ?? '';

				const optionExists = [...mappedFieldZGW.options]
					.some(option => option.value === value);

				mappedFieldZGW.value = optionExists ? value : '';
			}

			const mappedFieldDocumentTypeZGW = document.getElementById('mappedFieldDocumentTypeZGW');
			if (mappedFieldDocumentTypeZGW) {
				const value = field['mappedFieldDocumentTypeValueZGW'] ?? '';

				const optionExists = [...mappedFieldDocumentTypeZGW.options]
					.some(option => option.value === value);

				mappedFieldDocumentTypeZGW.value = optionExists ? value : '';
			}

			const uploadFieldDescriptionZGW = document.getElementById('uploadFieldDescriptionZGW');
			if(uploadFieldDescriptionZGW) uploadFieldDescriptionZGW.value = field['uploadFieldDescriptionValueZGW'] ?? '';

			const linkedFieldKvKBranchNumber = document.getElementById('linkedFieldKvKBranchNumber');
			if (linkedFieldKvKBranchNumber) {
				linkedFieldKvKBranchNumber.checked = String(field['linkedFieldValueKvKBranchNumber'] ?? '0') === '1';
			}

			const mappedFieldInvolvedIdentificationZGW = document.getElementById('mappedFieldInvolvedIdentificationZGW');
			if(mappedFieldInvolvedIdentificationZGW) mappedFieldInvolvedIdentificationZGW.value = field['mappedFieldValueInvolvedIdentificationZGW'] ?? '';

			const mappedFieldContactPersonRoleZGW = document.getElementById('mappedFieldContactPersonRoleZGW');
			if(mappedFieldContactPersonRoleZGW) mappedFieldContactPersonRoleZGW.value = field['mappedFieldValueContactPersonRoleZGW'] ?? '';
		});
	});
</script>
