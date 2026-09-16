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
		const button = document.getElementById('owcZGWPdfExclude');

		if (!button) return;

		const cssClassInput = document.getElementById('field_css_class');

		const EXCLUDE_CLASS = 'exclude';

		const splitClasses = (value) => String(value ?? '').match(/\S+/g) ?? [];

		let selectedField = null;

		function renderState(cssClass) {
			const excluded = splitClasses(cssClass).includes(EXCLUDE_CLASS);

			button.setAttribute('aria-pressed', excluded ? 'true' : 'false');
			button.textContent = excluded
				? '<?php echo esc_js( __( 'Opnemen in PDF', 'owc-gravityforms-zgw' ) ); ?>'
				: '<?php echo esc_js( __( 'Uitsluiten van PDF', 'owc-gravityforms-zgw' ) ); ?>';

			const status = document.getElementById('owcZGWPdfExcludeStatus');

			if (status) {
				status.textContent = excluded
					? '<?php echo esc_js( __( 'Dit veld wordt niet opgenomen in de PDF.', 'owc-gravityforms-zgw' ) ); ?>'
					: '<?php echo esc_js( __( 'Dit veld wordt opgenomen in de PDF.', 'owc-gravityforms-zgw' ) ); ?>';
			}
		}

		jQuery.each(fieldSettings, function (index, value) {
			fieldSettings[index] += ', .zgw_pdf_exclude_setting';
		});

		jQuery(document).on('gform_load_field_settings', function (event, field, form) {
			selectedField = field;

			renderState(field['cssClass']);
		});

		button.addEventListener('click', function (event) {
			event.preventDefault();

			if (!selectedField) return;

			const classes = splitClasses(selectedField['cssClass']);
			const index = classes.indexOf(EXCLUDE_CLASS);

			if (index === -1) {
				classes.push(EXCLUDE_CLASS);
			} else {
				classes.splice(index, 1);
			}

			const cssClass = classes.join(' ');

			SetFieldProperty('cssClass', cssClass);

			if (cssClassInput) cssClassInput.value = cssClass;

			renderState(cssClass);
		});

		if (cssClassInput) {
			cssClassInput.addEventListener('input', function () {
				renderState(this.value);
			});
		}
	});
</script>
