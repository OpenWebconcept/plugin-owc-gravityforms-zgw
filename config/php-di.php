<?php

use DI\Container;
use OWCGravityFormsZGW\Auth\DigiD;
use OWCGravityFormsZGW\Settings\Settings;

return array(
	/**
	 * Suppliers
	 */
	'suppliers'                   => array(
		'openzaak'   => 'OpenZaak',
		'decos-join' => 'DecosJOIN',
		'rx-mission' => 'RxMission',
		'xxllnc'     => 'XXLNC',
		'procura'    => 'Procura',
	),

	/**
	 * Specific client settings.
	 */
	'oz.enabled'                  => function (Container $container ) {
		return (bool) $container->make( 'zgw.get-configured-client', array( 'openzaak' ) );
	},
	'oz.api-client-settings'      => function (Container $container ) {
		return $container->make( 'zgw.get-configured-client', array( 'openzaak' ) );
	},
	'xxllnc.enabled'              => function (Container $container ) {
		return (bool) $container->make( 'zgw.get-configured-client', array( 'xxllnc' ) );
	},
	'xxllnc.api-client-settings'  => function (Container $container ) {
		return $container->make( 'zgw.get-configured-client', array( 'xxllnc' ) );
	},
	'dj.enabled'                  => function (Container $container ) {
		return (bool) $container->make( 'zgw.get-configured-client', array( 'decosjoin' ) );
	},
	'dj.api-client-settings'      => function (Container $container ) {
		return $container->make( 'zgw.get-configured-client', array( 'decosjoin' ) );
	},
	'rx.enabled'                  => function (Container $container ) {
		return (bool) $container->make( 'zgw.get-configured-client', array( 'rxmission' ) );
	},
	'rx.api-client-settings'      => function (Container $container ) {
		return $container->make( 'zgw.get-configured-client', array( 'rxmission' ) );
	},
	'procura.enabled'             => function (Container $container ) {
		return (bool) $container->make( 'zgw.get-configured-client', array( 'procura' ) );
	},
	'procura.api-client-settings' => function (Container $container ) {
		return $container->make( 'zgw.get-configured-client', array( 'procura' ) );
	},

	/**
	 * Generic client settings.
	 */
	'zgw.get-configured-client'   => function (Container $container, string $type, string $name ) {
		$clients = $container->make( 'zgw.api.settings', array( 'zgw-api-configured-clients' ) ) ?: array();
		$clients = array_filter(
			$clients,
			function ($client ) use ($name ) {
				return $name === $client['client_type'];
			}
		);
		$client  = reset( $clients );

		return is_array( $client ) && 0 < count( $client ) ? $client : array();
	},
	'zgw.api-configured-clients'  => function (Container $container ) {
		return $container->make( 'zgw.api.settings', array( 'zgw-api-configured-clients' ) );
	},
	'zgw.api.settings'            => function (Container $container, string $type, string $name ) {
		return Settings::make( 'zgw_api_settings' )->get( $name );
	},
	'zgw.rsin'                    => function (Container $container ) {
		return $container->make( 'zgw.addon.settings', array( 'owc-gf-zgw-add-on-organization-rsin' ) );
	},
	'zgw.addon.settings'          => function (Container $container, string $type, string $name ) {
		return Settings::make( 'gravityformsaddon_owc-gravityforms-zgw_settings' )->get( $name );
	},
	'digid.current_user_bsn'      => DigiD::make()->bsn(),

	/**
	 * ZGW error logging.
	 */
	'message.logger.active'       => function (Container $container ) {
		return (bool) $container->make( 'zgw.addon.settings', array( 'owc-gf-zgw-add-on-logging-enabled' ) );
	},
	'message.logger.path'         => dirname( __DIR__ ) . '/owc-zgw-log.json',
	'message.logger'              => function (Container $container ) {
		$logger = new \Monolog\Logger( 'owc_zgw_log' );

		$handler = new \Monolog\Handler\StreamHandler(
			$container->get( 'message.logger.path' ),
			\Monolog\Logger::DEBUG
		);

		$handler->setFormatter( new \Monolog\Formatter\JsonFormatter() );
		$logger->pushHandler( $handler );

		$logger->pushProcessor( new \Monolog\Processor\IntrospectionProcessor() );

		return $logger;
	},

	'logger.zgw'                  => function (Container $container ) {
		return new \OWCGravityFormsZGW\LoggerZGW( $container->get( 'message.logger' ) );
	},
);
