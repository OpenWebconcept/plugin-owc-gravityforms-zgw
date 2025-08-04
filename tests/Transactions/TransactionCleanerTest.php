<?php

use OWCGravityFormsZGW\Transactions\TransactionCleaner;

beforeEach(
	function () {
		WP_Mock::setUp();

		class FakeWPQueryCleaner
		{
			public $posts = array();

			public function __construct(array $args ) {
				$threshold = $args['meta_query'][0]['value'];

				$posts = array(
					101 => '2023-01-01 10:00:00', // very old
					102 => 'yesterday 12:00:00',  // recent, should not be deleted if threshold > 1
					103 => '2022-12-31 23:59:59', // very old
				);

				foreach ($posts as $id => $date) {
					if ($date < $threshold) {
						$this->posts[] = $id;
					}
				}
			}
		}

		WP_Mock::userFunction(
			'wp_delete_post',
			array(
				'return' => function ($post_id, $force_delete = false ) {
					TransactionCleanerTest::$deletedPosts[] = $post_id;
					return true;
				},
			)
		);

		WP_Mock::userFunction(
			'wp_reset_postdata',
			array(
				'return' => null,
			)
		);
	}
);

it(
	'only deletes transactions older than threshold',
	function () {
		$factory = function ($args ) {
			return new FakeWPQueryCleaner( $args );
		};

		$cleaner = new TransactionCleaner( $factory );
		$cleaner->delete_old_transactions( 30 );

		expect( TransactionCleanerTest::$deletedPosts )->toBe( array( 101, 103 ) );
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

class TransactionCleanerTest
{
	public static $deletedPosts = array();
}
