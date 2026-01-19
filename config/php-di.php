<?php

declare(strict_types=1);

use OWCGravityFormsZGW\Auth\DigiD;
use OWCGravityFormsZGW\Auth\eHerkenning;
use OWCGravityFormsZGW\Settings\Settings;
use OWCGravityFormsZGW\Vendor_Prefixed\DI\Container;

return array(
	/**
	 * Suppliers
	 *
	 * Best kept in sync with:
	 * https://github.com/OpenWebconcept/owc-zgw-api/blob/main/src/WordPress/SettingsProvider.php#L50
	 */
	'suppliers'                    => array(
		'openzaak'  => 'OpenZaak',
		'xxllnc'    => 'XXLLNC',
		'rxmission' => 'RxMission',
		'decosjoin' => 'Decos JOIN',
		'procura'   => 'Procura',
	),

	/**
	 * Generic client settings.
	 */
	'zgw.get-configured-client'    => function (Container $container, string $type, string $name ) {
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
	'zgw.api-configured-clients'   => function (Container $container ) {
		return $container->make( 'zgw.api.settings', array( 'zgw-api-configured-clients' ) );
	},
	'zgw.api.settings'             => function (Container $container, string $type, string $name ) {
		return Settings::make( 'zgw_api_settings' )->get( $name );
	},
	'zgw.rsin'                     => function (Container $container ) {
		return $container->make( 'zgw.addon.settings', array( 'owc-gf-zgw-add-on-organization-rsin' ) );
	},
	'zgw.addon.settings'           => function (Container $container, string $type, string $name ) {
		return Settings::make( 'gravityformsaddon_owc-gravityforms-zgw_settings' )->get( $name );
	},
	'zgw.site_options'             => OWCGravityFormsZGW\Singletons\SiteOptionsSingleton::get_instance( get_option( OWC_GRAVITYFORMS_ZGW_SITE_OPTION_NAME, array() ) ),
	'zgw.site_options_cmb2'        => OWCGravityFormsZGW\Singletons\SiteOptionsSingletonCMB2::get_instance( get_option( OWC_GRAVITYFORMS_ZGW_SITE_OPTION_NAME_CMB2, array() ) ),
	'digid.current_user_bsn'       => DigiD::make()->bsn(),
	'eherkenning.current_user_kvk' => eHerkenning::make()->kvk(),

	/**
	 * ZGW error logging.
	 */
	'message.logger.active'        => function (Container $container ) {
		return (bool) $container->make( 'zgw.addon.settings', array( 'owc-gf-zgw-add-on-logging-enabled' ) );
	},
	'message.logger.path'          => sprintf( '%s/owc-zgw-log.json', dirname( ABSPATH ) ),
	'message.logger'               => function (Container $container ) {
		$logger   = new OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Logger( 'owc_zgw_log' );
		$maxFiles = apply_filters( 'owcgfzgw::logger/rotating_filer_handler_max_files', OWC_GRAVITYFORMS_ZGW_LOGGER_DEFAULT_MAX_FILES );

		$handler = ( new OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Handler\RotatingFileHandler(
			filename: $container->get( 'message.logger.path' ),
			maxFiles: is_int( $maxFiles ) && 0 < $maxFiles ? $maxFiles : OWC_GRAVITYFORMS_ZGW_LOGGER_DEFAULT_MAX_FILES,
			level: OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Level::Debug
		) )->setFormatter( new OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Formatter\JsonFormatter() );

		$logger->pushHandler( $handler );
		$logger->pushProcessor( new OWCGravityFormsZGW\Vendor_Prefixed\Monolog\Processor\IntrospectionProcessor() );

		return $logger;
	},

	'logger.zgw'                   => function (Container $container ) {
		return new \OWCGravityFormsZGW\LoggerZGW( $container->get( 'message.logger' ) );
	},
);
