<?php

/**
 * Autoloader for classes.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

spl_autoload_register(
	function (string $classname ) {
		$classmap = array(
			'OWCGravityFormsZGW' => __DIR__ . '/',
		);
		$parts    = explode( '\\', $classname );

		$namespace    = array_shift( $parts );
		$classifiable = array_pop( $parts ) . '.php';

		if ( ! array_key_exists( $namespace, $classmap )) {
			return;
		}

		$path = implode( DIRECTORY_SEPARATOR, $parts );
		$file = $classmap[ $namespace ] . $path . DIRECTORY_SEPARATOR . $classifiable;

		if ( ! file_exists( $file ) && ! class_exists( $classname )) {
			return;
		}

		require_once $file;
	}
);
