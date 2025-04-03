<?php
/**
 * Field settings.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use GFAddon;

/**
 * Field settings.
 *
 * @since 1.0.0
 */
class ZGWAddon extends GFAddon
{
	/**
	 * Subview slug.
	 *
	 * @var string
	 */
	protected $_slug = OWC_GRAVITYFORMS_ZGW_PLUGIN_SLUG;

	/**
	 * The complete title of the Add-On.
	 *
	 * @var string
	 */
	protected $_title = 'OWC ZGW';

	/**
	 * The short title of the Add-On to be used in limited spaces.
	 *
	 * @var string
	 */
	protected $_short_title = 'OWC ZGW';

	/**
	 * Instance object
	 *
	 * @var self
	 */
	private static $_instance = null;

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the form settings
	 */
	protected $_capabilities_form_settings = array( 'gravityforms_edit_forms' );

	/**
	 * Singleton loader.
	 */
	public static function get_instance(): self
	{
		if (null == self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function plugin_settings_fields()
	{
		$prefix = OWC_GRAVITYFORMS_ZGW_ADD_ON_SETTINGS_PREFIX;

		return array(
			array(
				'title'  => __( 'Organisatie', 'owc-gravityforms-zgw' ),
				'class'  => 'gform-settings-panel--half',
				'fields' => array(
					array(
						'label'       => __( 'RSIN', 'owc-gravityforms-zgw' ),
						'type'        => 'text',
						'class'       => 'medium',
						'name'        => "{$prefix}-organization-rsin",
						'required'    => true,
						'description' => 'Het RSIN is een uniek identificatienummer dat wordt gebruikt voor gegevensuitwisseling met overheidsinstanties.',
					),
				),
			),
			array(
				'title'  => __( 'Logboekinstellingen', 'owc-gravityforms-zgw' ),
				'class'  => 'gform-settings-panel--half',
				'fields' => array(
					array(
						'label'         => __( 'Logging inschakelen', 'prefill-gravity-forms' ),
						'type'          => 'toggle',
						'name'          => "{$prefix}-logging-enabled",
						'required'      => false,
						'default_value' => false,
						'description'   => __( 'Schakel deze optie in om het loggen van systeemactiviteiten en foutmeldingen te activeren. Dit kan nuttig zijn voor het opsporen en oplossen van problemen binnen de plug-in.', 'owc-gravityforms-zgw' ),
					),
				),
			),
		);
	}
}
