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

		const LABEL_EXCLUDE = '<?php echo esc_js( __( 'Uitsluiten van PDF', 'owc-gravityforms-zgw' ) ); ?>';
		const LABEL_INCLUDE = '<?php echo esc_js( __( 'Opnemen in PDF', 'owc-gravityforms-zgw' ) ); ?>';
		const STATUS_EXCLUDED = '<?php echo esc_js( __( 'Dit veld wordt niet opgenomen in de PDF.', 'owc-gravityforms-zgw' ) ); ?>';
		const STATUS_INCLUDED = '<?php echo esc_js( __( 'Dit veld wordt opgenomen in de PDF.', 'owc-gravityforms-zgw' ) ); ?>';
		const STATUS_COLLISION = '<?php echo esc_js( __( 'Dit veld wordt niet opgenomen in de PDF door de CSS-class: %s. Verwijder die class om het veld weer op te nemen.', 'owc-gravityforms-zgw' ) ); ?>';

		const splitClasses = (value) => String(value ?? '').match(/\S+/g) ?? [];

		/**
		 * Gravity PDF leaves a field out of the PDF when its cssClass string *contains*
		 * "exclude" (Model_PDF::field_middle_exclude uses strpos), so a class such as
		 * "exclude-on-mobile" excludes the field as well. This button only owns the exact
		 * "exclude" token, so colliding classes are reported but never rewritten.
		 */
		const collidingClasses = (classes) => classes.filter(
			(cssClass) => cssClass !== EXCLUDE_CLASS && cssClass.includes(EXCLUDE_CLASS)
		);

		let selectedField = null;

		function renderState(cssClass) {
			const classes = splitClasses(cssClass);
			const colliding = collidingClasses(classes);
			const excluded = colliding.length > 0 || classes.includes(EXCLUDE_CLASS);

			button.setAttribute('aria-pressed', excluded ? 'true' : 'false');
			button.disabled = colliding.length > 0;
			button.textContent = excluded ? LABEL_INCLUDE : LABEL_EXCLUDE;

			const status = document.getElementById('owcZGWPdfExcludeStatus');

			if (!status) return;

			if (colliding.length > 0) {
				status.textContent = STATUS_COLLISION.replace('%s', colliding.join(', '));

				return;
			}

			status.textContent = excluded ? STATUS_EXCLUDED : STATUS_INCLUDED;
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

			if (collidingClasses(classes).length > 0) return;

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
