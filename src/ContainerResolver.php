<?php
/**
 * Resolve entries from the DI-container.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use DI\Container;

/**
 * Class ContainerResolver
 *
 * @since 1.0.0
 */
class ContainerResolver
{
	protected Container $container;

	final private function __construct()
	{
		$this->container = Bootstrap::get_container();
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
	public function get(string $key )
	{
		return $this->container->get( $key ) ?? null;
	}
}
