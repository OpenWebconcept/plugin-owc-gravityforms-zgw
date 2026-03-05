<?php
/**
 * Exit when accessed directly.
 *
 * @package owc-gravityforms-zgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$properties = $properties ?? array();
?>

<li class="field_setting zgw_mapping_setting">
	<h3 style="margin-top: 0px;">Zaak creatie</h3>
</li>

<li class="field_setting zgw_mapping_setting">
	<label for="mappedFieldZGW" class="section_label">
		<?php esc_html_e( 'Mapping', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappedFieldZGW" onchange="SetFieldProperty('mappedFieldValueZGW', this.value);">
		<option value=""><?php esc_html_e( 'Selecteer een optie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="bronorganisatie"><?php esc_html_e( 'Bronorganisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="zaaktype"><?php esc_html_e( 'Zaaktype', 'owc-gravityforms-zgw' ); ?></option>
		<option value="omschrijving"><?php esc_html_e( 'Omschrijving', 'owc-gravityforms-zgw' ); ?></option>
		<option value="toelichting"><?php esc_html_e( 'Toelichting', 'owc-gravityforms-zgw' ); ?></option>
		<option value="registratiedatum"><?php esc_html_e( 'Registratiedatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="verantwoordelijkeOrganisatie"><?php esc_html_e( 'Verantwoordelijke organisatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="startdatum"><?php esc_html_e( 'Startdatum', 'owc-gravityforms-zgw' ); ?></option>
		<option value="informatieobject"><?php esc_html_e( 'Informatieobject', 'owc-gravityforms-zgw' ); ?></option>
		<optgroup label="<?php esc_html_e( 'Zaakeigenschappen', 'owc-gravityforms-zgw' ); ?>">
			<?php foreach ( $properties as $property ) : ?>
				<option value="<?php echo esc_attr( $property['value'] ); ?>">
					<?php echo esc_html( $property['label'] ); ?>
				</option>
			<?php endforeach; ?>
	</select>
	<small><?php esc_html_e( 'Selecteer het veld dat gebruikt moet worden bij het aanmaken van de zaak.', 'owc-gravityforms-zgw' ); ?></small>
</li>

<li class="zgw_kvk_setting field_setting">
	<h3 style="margin-top: 0px;">Kamer van koophandel (KVK)</h3>
</li>

<li class="zgw_kvk_setting field_setting">
	<input type="checkbox" id="linkedFieldKvKBranchNumber" name="linkedFieldKvKBranchNumber" onchange="SetFieldProperty('linkedFieldValueKvKBranchNumber', this.checked ? '1' : '0');">
	<label for="linkedFieldKvKBranchNumber" class="section_label">
		<?php esc_html_e( 'KVK vestigingsnummer', 'owc-gravityforms-zgw' ); ?>
	</label>
	<small><?php esc_html_e( 'Kies dit veld om het KVK vestigingsnummer te koppelen aan de rol van de indiener.', 'owc-gravityforms-zgw' ); ?></small>
</li>

<li class="field_setting zgw_involved_identification_mapping_setting zgw_contact_person_role_mapping_setting">
	<h3 style="margin-top: 0px;">Rol van de Zaak</h3>
</li>
<li class="field_setting zgw_involved_identification_mapping_setting">
	<label for="mappedFieldInvolvedIdentificationZGW" class="section_label">
		<?php esc_html_e( 'Betrokkene identificatie', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappedFieldInvolvedIdentificationZGW" onchange="SetFieldProperty('mappedFieldValueInvolvedIdentificationZGW', this.value);">
		<option value=""><?php esc_html_e( 'Selecteer een optie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="inpBsn"><?php esc_html_e( 'inpBsn', 'owc-gravityforms-zgw' ); ?></option>
		<option value="anpIdentificatie"><?php esc_html_e( 'anpIdentificatie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="inpA_nummer"><?php esc_html_e( 'inpA_nummer', 'owc-gravityforms-zgw' ); ?></option>
		<option value="geslachtsnaam"><?php esc_html_e( 'geslachtsnaam', 'owc-gravityforms-zgw' ); ?></option>
		<option value="voorvoegselGeslachtsnaam"><?php esc_html_e( 'voorvoegselGeslachtsnaam', 'owc-gravityforms-zgw' ); ?></option>
		<option value="voorletters"><?php esc_html_e( 'voorletters', 'owc-gravityforms-zgw' ); ?></option>
		<option value="voornamen"><?php esc_html_e( 'voornamen', 'owc-gravityforms-zgw' ); ?></option>
		<option value="geslachtsaanduiding"><?php esc_html_e( 'geslachtsaanduiding', 'owc-gravityforms-zgw' ); ?></option>
		<option value="geboortedatum"><?php esc_html_e( 'geboortedatum', 'owc-gravityforms-zgw' ); ?></option>
		<optgroup label="<?php esc_html_e( 'Verblijfsadres', 'owc-gravityforms-zgw' ); ?>">
			<option value="verblijfsadres.aoaIdentificatie"><?php esc_html_e( 'aoaIdentificatie', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.wplWoonplaatsNaam"><?php esc_html_e( 'wplWoonplaatsNaam', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.gorOpenbareRuimteNaam"><?php esc_html_e( 'gorOpenbareRuimteNaam', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.aoaPostcode"><?php esc_html_e( 'aoaPostcode', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.aoaHuisnummer"><?php esc_html_e( 'aoaHuisnummer', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.aoaHuisletter"><?php esc_html_e( 'aoaHuisletter', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.aoaHuisnummertoevoeging"><?php esc_html_e( 'aoaHuisnummertoevoeging', 'owc-gravityforms-zgw' ); ?></option>
			<option value="verblijfsadres.inpLocatiebeschrijving"><?php esc_html_e( 'inpLocatiebeschrijving', 'owc-gravityforms-zgw' ); ?></option>
		</optgroup>
		<optgroup label="<?php esc_html_e( 'subVerblijfBuitenland', 'owc-gravityforms-zgw' ); ?>">
			<option value="subVerblijfBuitenland.lndLandcode"><?php esc_html_e( 'lndLandcode', 'owc-gravityforms-zgw' ); ?></option>
			<option value="subVerblijfBuitenland.lndLandnaam"><?php esc_html_e( 'lndLandnaam', 'owc-gravityforms-zgw' ); ?></option>
			<option value="subVerblijfBuitenland.subAdresBuitenland_1"><?php esc_html_e( 'subAdresBuitenland_1', 'owc-gravityforms-zgw' ); ?></option>
			<option value="subVerblijfBuitenland.subAdresBuitenland_2"><?php esc_html_e( 'subAdresBuitenland_2', 'owc-gravityforms-zgw' ); ?></option>
			<option value="subVerblijfBuitenland.subAdresBuitenland_3"><?php esc_html_e( 'subAdresBuitenland_3', 'owc-gravityforms-zgw' ); ?></option>
		</optgroup>
	</select>
</li>

<li class="field_setting zgw_contact_person_role_mapping_setting">
	<label for="mappedFieldContactPersonRoleZGW" class="section_label">
		<?php esc_html_e( 'Contactpersoon rol', 'owc-gravityforms-zgw' ); ?>
	</label>

	<select id="mappedFieldContactPersonRoleZGW" onchange="SetFieldProperty('mappedFieldValueContactPersonRoleZGW', this.value);">
		<option value=""><?php esc_html_e( 'Selecteer een optie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="emailadres"><?php esc_html_e( 'E-mailadres', 'owc-gravityforms-zgw' ); ?></option>
		<option value="functie"><?php esc_html_e( 'Functie', 'owc-gravityforms-zgw' ); ?></option>
		<option value="telefoonnummer"><?php esc_html_e( 'Telefoonnummer', 'owc-gravityforms-zgw' ); ?></option>
		<option value="naam"><?php esc_html_e( 'Naam', 'owc-gravityforms-zgw' ); ?></option>
	</select>
</li>
