<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$settings_field_id                           = $settings_field_id ?? '';
$available_user_roles                        = is_array( $available_user_roles ?? null ) ? $available_user_roles : array();
$selectable_suppliers                        = is_array( $selectable_suppliers ?? null ) ? $selectable_suppliers : array();
$owc_gf_zgw_transaction_user_roles           = is_array( $owc_gf_zgw_transaction_user_roles ?? null ) ? $owc_gf_zgw_transaction_user_roles : array();
$owc_zgw_transactions_report_recipient_email = $owc_zgw_transactions_report_recipient_email ?? '';
$owc_zgw_delay_after_zaak_creation_seconds   = $owc_zgw_delay_after_zaak_creation_seconds ?? '';
$owc_zgw_delay_after_zaak_creation_suppliers = is_array( $owc_zgw_delay_after_zaak_creation_suppliers ?? null ) ? $owc_zgw_delay_after_zaak_creation_suppliers : array();
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

<?php if ( 'owc_zgw_transactions_report_recipient_email' === $settings_field_id ) : ?>
	<input type="email" name="owc_gf_zgw_options[owc_zgw_transactions_report_recipient_email]" value="<?php echo esc_attr( $owc_zgw_transactions_report_recipient_email ); ?>" required>
<?php endif; ?>

<?php if ( 'owc_zgw_delay_after_zaak_creation_seconds' === $settings_field_id ) : ?>
	<input type="number" name="owc_gf_zgw_options[owc_zgw_delay_after_zaak_creation_seconds]" min="0" max="10" value="<?php echo esc_attr( $owc_zgw_delay_after_zaak_creation_seconds ); ?>" required>
<?php endif; ?>

<?php if ( 'owc_zgw_delay_after_zaak_creation_suppliers' === $settings_field_id ) : ?>
	<select name="owc_gf_zgw_options[owc_zgw_delay_after_zaak_creation_suppliers][]" style="width: 10em" multiple>
		<?php foreach ( $selectable_suppliers as $key => $supplier ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, $owc_zgw_delay_after_zaak_creation_suppliers, true ) ? 'selected' : ''; ?>>
				<?php echo esc_html( $supplier ); ?>
			</option>
		<?php endforeach; ?>
	</select>
<?php endif; ?>
