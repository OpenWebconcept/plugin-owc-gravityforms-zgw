<?php

namespace OWCGravityFormsZGW\Settings;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

class Settings
{
	protected string $option_name = 'zgw_api_settings';
	protected array $options      = array();

	final private function __construct()
	{
		$this->options = get_option( $this->option_name, array() );
	}

	/**
	 * @since 1.0.0
	 */
	public static function make(): self
	{
		return new static();
	}

	/**
	 * @since 1.0.0
	 */
	public function get(string $key ): mixed
	{
		return $this->options[ $key ] ?? '';
	}
}
