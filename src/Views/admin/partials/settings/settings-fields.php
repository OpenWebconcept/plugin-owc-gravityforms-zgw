<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$settings_field_id                 = $settings_field_id ?? '';
$available_user_roles              = is_array( $available_user_roles ?? null ) ? $available_user_roles : array();
$owc_gf_zgw_transaction_user_roles = is_array( $owc_gf_zgw_transaction_user_roles ?? null ) ? $owc_gf_zgw_transaction_user_roles : array();
?>

<?php if ( 'owc_gf_zgw_transaction_user_roles' === $settings_field_id ) : ?>
<select name="owc_gf_zgw_options[owc_gf_zgw_transaction_user_roles][]" style="width: 10em" multiple>
	<?php foreach ( $available_user_roles as $role_name => $role_info ) : ?>
	<option value="<?php echo esc_attr( $role_name ); ?>" <?php echo in_array( $role_name, $owc_gf_zgw_transaction_user_roles, true ) ? 'selected' : ''; ?>>
		<?php echo esc_html( translate_user_role( $role_info['name'], 'default' ) ); ?>
	</option>
	<?php endforeach; ?>
</select>
<?php endif; ?>
