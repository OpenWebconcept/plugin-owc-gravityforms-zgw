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

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h2><?php esc_html_e( 'Zaaktypen en informatieobjecttypen cache', 'owc-gravityforms-zgw' ); ?></h2>
	<?php
	/** @var FormSettingsCacheController $cache_controller */
	owc_gravityforms_zgw_render_view(
		'admin/partials/settings/description-form-settings-cache',
		array(
			'cache_controller' => $cache_controller,
		)
	);
	?>
</div>
