<?php

declare(strict_types=1);

namespace OWCGravityFormsZGW\GravityForms\FormSettingAdapters;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Closure;
use Exception;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Endpoints\Filter\ResultaattypenFilter;
use OWC\ZGW\Support\Collection;

abstract class Adapter
{
	protected const TRANSIENT_LIFETIME_IN_SECONDS = 64800; // 18 hours.

	protected Client $client;

	public function __construct(Client $client )
	{
		$this->client = $client;
	}

	/**
	 * @since 1.0.0
	 */
	protected function transient_key_prefix(): string
	{
		return strtolower( str_replace( '\\', '-', $this->client::class ) );
	}

	abstract public function handle(): array;

	/**
	 * @since 1.0.0
	 */
	protected function get_types(string $transient_key, string $endpoint, Closure $prepare_callback, string $empty_message ): array
	{
		$types = get_transient( $transient_key );

		if (is_array( $types ) && $types) {
			return $types;
		}

		$types = $this->fetch_types( $empty_message, $endpoint );
		$types = $this->prepare_types( $types, $prepare_callback );

		if (empty( $types )) {
			return array();
		}

		set_transient( $transient_key, $types, self::TRANSIENT_LIFETIME_IN_SECONDS ); // 18 hours.

		return $types;
	}

	/**
	 * @since 1.0.0
	 */
	protected function fetch_types(string $empty_message, string $endpoint ): array
	{
		$page             = 1;
		$types            = array();
		$requestException = '';

		while ($page) {
			try {
				$result = $this->client->$endpoint()->all( ( new ResultaattypenFilter() )->page( $page ) );
			} catch (Exception $e) {
				$requestException = $e->getMessage();

				break;
			}

			$types = array_merge( $types, $result->all() );
			$page  = $result->pageMeta()->getNextPageNumber();
		}

		$this->handle_empty_result( $types, $empty_message, $requestException );

		return $types;
	}

	/**
	 * @since 1.0.0
	 */
	protected function prepare_types(array $types, Closure $prepare_callback ): array
	{
		return (array) Collection::collect( $types )->map( $prepare_callback )->all();
	}

	/**
	 * @since 1.0.0
	 */
	protected function handle_empty_result(array $types, string $empty_message, string $requestException ): void
	{
		if (empty( $types )) {
			$exceptionMessage = $empty_message;

			if ( ! empty( $requestException )) {
				$exceptionMessage = sprintf( '%s %s', $exceptionMessage, $requestException );
			}

			throw new Exception( $exceptionMessage );
		}
	}

	/**
	 * @since 1.0.0
	 */
	protected function handle_no_choices(string $endpoint ): array
	{
		return array(
			array(
				'label' => __( sprintf( 'Kan de "%s" die horen bij de geselecteerde leverancier niet ophalen.', $endpoint ), 'owc-gravityforms-zaaksysteem' ),
			),
		);
	}
}
