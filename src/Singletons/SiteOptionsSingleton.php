<?php

declare(strict_types=1);

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
class SiteOptionsSingleton
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
			self::$instance = new SiteOptionsSingleton( $options );
		}

		return self::$instance;
	}

	public function transaction_user_roles(): array
	{
		return $this->options['owc_gf_zgw_transaction_user_roles'] ?? array();
	}

	public function transaction_report_recipient_email(): string
	{
		return $this->options['owc_zgw_transactions_report_recipient_email'] ?? '';
	}

	/**
	 * @since NEXT
	 */
	public function delay_after_zaak_creation_seconds(): int
	{
		$delay = (int) ( $this->options['owc_zgw_delay_after_zaak_creation_seconds'] ?? 0 );

		if ( $delay < 0 || $delay > 10 ) {
			return 0;
		}

		return $delay;
	}

	public function delay_after_zaak_creation_suppliers(): array
	{
		return $this->options['owc_zgw_delay_after_zaak_creation_suppliers'] ?? array();
	}
}
