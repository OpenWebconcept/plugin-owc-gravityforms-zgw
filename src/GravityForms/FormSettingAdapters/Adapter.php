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
	protected Client $client;

	public function __construct(Client $client )
	{
		$this->client = $client;
	}

	/**
	 * @since 1.0.0
	 */
	protected function transientKeyPrefix(): string
	{
		return strtolower( str_replace( '\\', '-', $this->client::class ) );
	}

	abstract public function handle(): array;

	/**
	 * @since 1.0.0
	 */
	protected function getTypes(string $transientKey, string $endpoint, Closure $prepareCallback, string $emptyMessage ): array
	{
		$types = get_transient( $transientKey );

		if (is_array( $types ) && $types) {
			return $types;
		}

		$types = $this->fetchTypes( $emptyMessage, $endpoint );
		$types = $this->prepareTypes( $types, $prepareCallback );

		if (empty( $types )) {
			return array();
		}

		set_transient( $transientKey, $types, 64800 ); // 18 hours.

		return $types;
	}

	/**
	 * @since 1.0.0
	 */
	protected function fetchTypes(string $emptyMessage, string $endpoint ): array
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

		$this->handleEmptyResult( $types, $emptyMessage, $requestException );

		return $types;
	}

	/**
	 * @since 1.0.0
	 */
	protected function prepareTypes(array $types, Closure $prepareCallback ): array
	{
		return (array) Collection::collect( $types )->map( $prepareCallback )->all();
	}

	/**
	 * @since 1.0.0
	 */
	protected function handleEmptyResult(array $types, string $emptyMessage, string $requestException ): void
	{
		if (empty( $types )) {
			$exceptionMessage = $emptyMessage;

			if ( ! empty( $requestException )) {
				$exceptionMessage = sprintf( '%s %s', $exceptionMessage, $requestException );
			}

			throw new Exception( $exceptionMessage );
		}
	}

	/**
	 * @since 1.0.0
	 */
	protected function handleNoChoices(string $endpoint ): array
	{
		return array(
			array(
				'label' => __( sprintf( 'Kan de "%s" die horen bij de geselecteerde leverancier niet ophalen.', $endpoint ), 'owc-gravityforms-zaaksysteem' ),
			),
		);
	}
}
