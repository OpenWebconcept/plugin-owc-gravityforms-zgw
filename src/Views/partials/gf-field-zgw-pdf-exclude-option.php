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

<li class="zgw_pdf_exclude_setting field_setting">
	<button type="button" id="owcZGWPdfExclude" class="button" aria-pressed="false" aria-describedby="owcZGWPdfExcludeStatus">
		<?php esc_html_e( 'Uitsluiten van PDF', 'owc-gravityforms-zgw' ); ?>
	</button>
	<span id="owcZGWPdfExcludeStatus" style="display: block; margin-top: 0.5rem;"><?php esc_html_e( 'Dit veld wordt opgenomen in de PDF.', 'owc-gravityforms-zgw' ); ?></span>
</li>
