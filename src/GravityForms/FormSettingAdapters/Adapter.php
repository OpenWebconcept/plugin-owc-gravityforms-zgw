<?php
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
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Closure;
use Exception;
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
		$page              = 1;
		$types             = array();
		$request_exception = '';

		while ($page) {
			try {
				$result = $this->client->$endpoint()->all( ( new ResultaattypenFilter() )->page( $page ) );
			} catch (Exception $e) {
				$request_exception = $e->getMessage();

				break;
			}

			$types = array_merge( $types, $result->all() );
			$page  = $result->pageMeta()->getNextPageNumber();
		}

		$this->handle_empty_result( $types, $empty_message, $request_exception );

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
	 * @throws Exception Message
	 */
	protected function handle_empty_result(array $types, string $empty_message, string $request_exception ): void
	{
		if (empty( $types )) {
			$exception_message = esc_html( $empty_message );

			if ( ! empty( $request_exception )) {
				$exception_message = sprintf( '%s %s', $exception_message, esc_html( $request_exception ) );
			}

			throw new Exception( esc_html( $exception_message ) );
		}
	}

	/**
	 * @since 1.0.0
	 */
	protected function handle_no_choices(string $endpoint ): array
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
