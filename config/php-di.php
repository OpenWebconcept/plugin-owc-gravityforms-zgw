<?php

use DI\Container;
use OWCGravityFormsZGW\Auth\DigiD;

return array(
	/**
	 * Suppliers
	 */
	'suppliers'                   => array(
		'openzaak'   => 'OpenZaak',
		'decos-join' => 'DecosJoin',
		'rx-mission' => 'RxMission',
		'xxllnc'     => 'Xxllnc',
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
		$clients = $container->make( 'zgw.settings', array( 'zgw-api-configured-clients' ) ) ?: array();
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
		return $container->make( 'zgw.settings', array( 'zgw-api-configured-clients' ) );
	},
	'zgw.settings'                => function (Container $container, string $type, string $name ) {
		return OWCGravityFormsZGW\Settings\Settings::make()->get( $name );
	},
	'zgw.rsin'                    => '807287684', // @todo add rsin setting and use it here.
	'digid.current_user_bsn'      => DigiD::make()->bsn(),
);
