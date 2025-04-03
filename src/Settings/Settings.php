<?php
/**
 * Settings.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Settings;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Settings.
 *
 * @since 1.0.0
 */
class Settings
{
	protected array $options = array();

	final private function __construct(string $settings_key )
	{
		$this->options = get_option( $settings_key, array() );
	}

	/**
	 * @since 1.0.0
	 */
	public static function make(string $settings_key ): self
	{
		return new static( $settings_key );
	}

	/**
	 * @since 1.0.0
	 */
	public function get(string $key ): mixed
	{
		return $this->options[ $key ] ?? '';
	}
}
