<?php


use OWCGravityFormsZGW\Tests\TestCase;

// Plugin files exit when ABSPATH is missing, define it before test files declare classes that extend them.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '' );
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses( TestCase::class )->in( __DIR__ );

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function get_option( $key, $default = false )
{
	if ( 'owc_zgw_settings' === $key ) {
		return array(
			'api_url' => 'https://api.example.com',
			'api_key' => 'secret-key',
		);
	}

	return $default;
}

/**
 * Registers a fake 'logger.zgw' in the plugin container so logged errors can be asserted.
 */
function fake_zgw_logger(): object
{
	require_once dirname( __DIR__ ) . '/vendor-prefixed/autoload.php';

	$logger = new class() {
		public array $errors = array();

		public function error( string $message, array $context = array() ): void
		{
			$this->errors[] = $message;
		}
	};

	$container = new OWCGravityFormsZGW\Vendor_Prefixed\DI\Container();
	$container->set( 'logger.zgw', $logger );

	$property = new ReflectionProperty( OWCGravityFormsZGW\Bootstrap::class, 'container' );
	$property->setValue( null, $container );

	return $logger;
}
