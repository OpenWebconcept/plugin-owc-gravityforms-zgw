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

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="<?php echo esc_url( 'options.php' ); ?>" method="post">
		<?php
		settings_fields( 'owc_gf_zgw_options_group' );
		do_settings_sections( 'owc-gf-zgw' );
		submit_button( __( 'Opslaan', 'owc-gravityforms-zgw' ) );
		?>
	</form>
</div>
