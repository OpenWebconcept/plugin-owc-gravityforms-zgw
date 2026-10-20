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
<p style="margin: 0;">
	<?php
	esc_html_e( 'Geef elke upload de naam van het gekoppelde documenttype. Bevat een formulier meerdere uploads met hetzelfde documenttype, dan wordt er een volgnummer toegevoegd (e.g. documenttype_1, documenttype_2). Kan de naam van het documenttype niet worden opgehaald, dan wordt de originele bestandsnaam gebruikt.', 'owc-gravityforms-zgw' );
	?>
</p>
