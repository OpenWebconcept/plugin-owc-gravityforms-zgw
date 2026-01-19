<?php
/**
 * Site Options Singleton.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Singletons;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 1.1.0
 */
class SiteOptionsSingletonCMB2
{
	private static $instance = null;
	private array $options;

	private function __construct( array $options )
	{
		$this->options = $options;
	}

	private function __clone()
	{
	}

	public function __wakeup()
	{
	}

	public static function get_instance( array $options ): self
	{
		if ( null === self::$instance ) {
			self::$instance = new SiteOptionsSingletonCMB2( $options['zgw-verzoeken-configured-clients'][0] ?? array() );
		}

		return self::$instance;
	}

	public function objecttypes_endpoint_url(): string
	{
		return $this->options['zgw_objecttypes_endpoint'] ?? '';
	}

	public function objects_endpoint_url(): string
	{
		return $this->options['zgw_objects_endpoint'] ?? '';
	}

	public function zgw_verzoeken_token(): string
	{
		return $this->options['zgw_verzoeken_token'] ?? '';
	}

	public function informationobjecttypes_endpoint_url(): string
	{
		return $this->options['zgw_informationobjecttypes_endpoint'] ?? '';
	}

	public function object_information_objects_endpoint(): string
	{
		return $this->options['zgw_object_information_objects_endpoint'] ?? '';
	}

	public function informationobject_endpoint(): string
	{
		return $this->options['zgw_informationobject_endpoint'] ?? '';
	}

	public function zgw_catalog_client_id(): string
	{
		return $this->options['zgw_catalog_client_id'] ?? '';
	}

	public function zgw_catalog_client_secret(): string
	{
		return $this->options['zgw_catalog_client_secret'] ?? '';
	}

	public function zgw_documents_client_id(): string
	{
		return $this->options['zgw_documents_client_id'] ?? '';
	}

	public function zgw_documents_client_secret(): string
	{
		return $this->options['zgw_documents_client_secret'] ?? '';
	}
}
