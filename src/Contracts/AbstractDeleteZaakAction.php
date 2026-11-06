<?php

declare(strict_types=1);

/**
 * Abstract delete "zaak" action.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.1.0
 */

namespace OWCGravityFormsZGW\Contracts;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWC\ZGW\Contracts\Client;
use OWC\ZGW\Http\Response;
use OWCGravityFormsZGW\ContainerResolver;
use OWCGravityFormsZGW\LoggerZGW;
use function OWC\ZGW\apiClient;

/**
 * Abstract delete "zaak" action.
 *
 * @since 1.1.0
 */
abstract class AbstractDeleteZaakAction
{
	protected Client $client;
	protected LoggerZGW $logger;

	public function __construct( array $supplier_config )
	{
		$this->client = apiClient( $supplier_config['name'] ?? '' );
		$this->logger = ContainerResolver::make()->get( 'logger.zgw' );
	}

	abstract public function delete( string $identifier ): Response;
}
