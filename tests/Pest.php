<?php


use OWCGravityFormsZGW\Tests\TestCase;

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

function get_option($key, $default = array() )
{
	if ('owc_zgw_settings' === $key) {
		return array(
			'api_url' => 'https://api.example.com',
			'api_key' => 'secret-key',
		);
	}

	return $default;
}
