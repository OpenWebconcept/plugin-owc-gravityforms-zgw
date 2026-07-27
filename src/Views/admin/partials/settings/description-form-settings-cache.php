<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

use OWCGravityFormsZGW\Controllers\FormSettingsCacheController;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<p style="margin: 0 0 1em;">
	<?php
	esc_html_e( 'De zaaktypen en informatieobjecttypen die worden getoond in de "Zaaksysteem" instellingen van een formulier worden 18 uur gecached en dagelijks om 04:00 automatisch ververst. Gebruik onderstaande knop om de cache handmatig te legen.', 'owc-gravityforms-zgw' );
	?>
</p>
<?php
/** @var FormSettingsCacheController $cache_controller */
$cache_controller->render_clear_cache_button();
