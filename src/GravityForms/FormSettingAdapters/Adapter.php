<?php

declare(strict_types=1);

/**
 * Adapter.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\GravityForms\FormSettingAdapters;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Closure;
use DateTimeImmutable;
use Exception;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\LoggerZGW;
use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Endpoints\Filter\ResultaattypenFilter;
use OWC\ZGW\Support\Collection;

/**
 * Adapter.
 *
 * @since 1.0.0
 */
abstract class Adapter
{
	protected const TRANSIENT_LIFETIME_IN_SECONDS = 64800; // 18 hours.

	protected Client $client;
	protected string $supplier_name;
	protected LoggerZGW $logger;
	protected bool $is_cron;

	public function __construct( Client $client, string $supplier_name, bool $is_cron = false )
	{
		$this->client        = $client;
		$this->logger        = ContainerResolver::make()->get( 'logger.zgw' );
		$this->supplier_name = $supplier_name;
		$this->is_cron       = $is_cron;
	}

	protected function transient_key_prefix(): string
	{
		return strtolower( str_replace( '\\', '-', $this->supplier_name ) );
	}

	abstract public function handle(): array;

	protected function get_types( string $transient_key, string $endpoint, Closure $prepare_callback, string $empty_message ): array
	{
		$types = get_transient( $transient_key );

		if ( is_array( $types ) && array() !== $types && false === $this->is_cron ) {
			return $types;
		}

		$types = $this->fetch_types( $empty_message, $endpoint );

		if ( 'zaaktypen' === $endpoint ) {
			$raw   = $types;
			$types = $this->filter_zaaktypen_by_version_date( $types );
			$this->replace_old_zaaktypen_after_fetch_in_form_settings( $raw, $types );
		}

		$types = $this->prepare_types( $types, $prepare_callback );

		if ( empty( $types ) ) {
			throw new Exception( sprintf( 'No valid %s types found after preparation.', $endpoint ), 400 );
		}

		set_transient( $transient_key, $types, self::TRANSIENT_LIFETIME_IN_SECONDS ); // 18 hours.

		return $types;
	}

	protected function fetch_types( string $empty_message, string $endpoint ): array
	{
		$page              = 1;
		$types             = array();
		$request_exception = '';

		while ( $page ) {
			try {
				$result = $this->client->$endpoint()->all( ( new ResultaattypenFilter() )->page( $page ) );
			} catch ( Exception $e ) {
				$request_exception = $e->getMessage();

				break;
			}

			$types = array_merge( $types, $result->all() );
			$page  = $result->pageMeta()->getNextPageNumber();

			if ( null === $page ) {
				break;
			}
		}

		$this->handle_empty_result( $types, $empty_message, $request_exception );

		return $types;
	}

	/**
	 * Prepares an array of types by applying a callback and filtering out invalid items.
	 * Also sorts the resulting array alphabetically by the 'label' key.
	 */
	protected function prepare_types( array $types, Closure $prepare_callback ): array
	{
		$types = (array) Collection::collect( $types )->map( $prepare_callback )->filter(
			function ( $item ) {
				return isset( $item['name'], $item['label'], $item['value'] ) && is_string( $item['name'] ) && is_string( $item['label'] ) && is_string( $item['value'] );
			}
		)->all();

		usort(
			$types,
			function ( $a, $b ) {
				return strcmp( $a['label'], $b['label'] );
			}
		);

		return $types;
	}

	/**
	 * Called after zaaktypen are fetched and filtered. Subclasses can override to act on the data.
	 *
	 * @since 1.10.0
	 */
	protected function replace_old_zaaktypen_after_fetch_in_form_settings( array $raw, array $filtered ): void {}

	/**
	 * Filters an array of zaaktypen to ensure unique 'omschrijvingen' (description) and keeping the one with the latest 'versiedatum' (version date).
	 *
	 * @since 1.10.0
	 */
	private function filter_zaaktypen_by_version_date( array $zaaktypen ): array
	{
		$filtered = array();

		foreach ( $zaaktypen as $zaaktype ) {
			$omschrijving = trim( (string) ( $zaaktype->omschrijving ?? '' ) );

			if ( '' === $omschrijving ) {
				continue;
			}

			// If we haven't seen this description before, add it to the filtered list.
			if ( ! isset( $filtered[ $omschrijving ] ) ) {
				$filtered[ $omschrijving ] = $zaaktype;

				continue;
			}

			$existing_versiedatum = $this->get_zaaktype_timestamp( $filtered[ $omschrijving ] );
			$current_versiedatum  = $this->get_zaaktype_timestamp( $zaaktype );

			if ( $current_versiedatum > $existing_versiedatum ) {
				$filtered[ $omschrijving ] = $zaaktype;
			}
		}

		return array_values( $filtered );
	}

	/**
	 * @since 1.10.0
	 */
	protected function get_zaaktype_timestamp( object $zaaktype ): int
	{
		if ( ! $zaaktype->versiedatum instanceof DateTimeImmutable ) {
			return 0;
		}

		$timestamp = $zaaktype->versiedatum->getTimestamp();

		return false === $timestamp ? 0 : $timestamp;
	}

	/**
	 * @throws Exception Message
	 */
	protected function handle_empty_result( array $types, string $empty_message, string $request_exception ): void
	{
		if ( 0 === count( $types ) ) {
			$exception_message = esc_html( $empty_message );

			if ( ! empty( $request_exception ) ) {
				$exception_message = sprintf( '%s %s', $exception_message, esc_html( $request_exception ) );
			}

			throw new Exception( esc_html( $exception_message ), 400 );
		}
	}

	protected function handle_no_choices( string $endpoint ): array
	{
		// translators: %s: The endpoint that could not be retrieved.
		$message = sprintf( __( 'Kan de "%s" die horen bij de geselecteerde leverancier niet ophalen.', 'owc-gravityforms-zaaksysteem' ), $endpoint );

		return array(
			array(
				'label' => $message,
			),
		);
	}
}
