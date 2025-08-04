<?php

use OWCGravityFormsZGW\Transactions\TransactionMailer;

beforeEach(
	function () {
		WP_Mock::setUp();

		$this->sent_mail = null;

		// Mock WordPress functions
		WP_Mock::userFunction(
			'get_post_stati',
			array(
				'return' => array( 'transaction_success', 'transaction_failed' ),
			)
		);

		WP_Mock::userFunction(
			'is_email',
			array(
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'current_time',
			array(
				'return' => '2024-05-01 12:00:00',
			)
		);

		WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return_in_order' => array( 123, '2024-05-01 12:00:00' ),
			)
		);

		WP_Mock::userFunction(
			'wp_mail',
			array(
				'return' => function ($to, $subject, $message ) {
					test()->sent_mail = compact( 'to', 'subject', 'message' );
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

afterEach(
	function () {
		WP_Mock::tearDown();
	}
);

it(
	'does NOT send a report email when there are no failed transactions',
	function () {
		$factory = function ($args ) {
			return new class($args) {
				public $posts = array();
				public function __construct($args ) {
					$statuses    = (array) $args['post_status'];
					$all_posts   = array(
						(object) array(
							'ID'          => 1,
							'post_type'   => 'owc_zgw_transaction',
							'post_status' => 'transaction_success',
						),
					);
					$this->posts = array_filter( $all_posts, fn($p ) => in_array( $p->post_status, $statuses, true ) );
				}
				public function have_posts() {
					return ! empty( $this->posts ); }
			};
		};

		$mailer = new TransactionMailer( $factory );
		$mailer->send_report( 'default@example.com', 'recipient@example.com' );

		expect( $this->sent_mail )->toBeNull();
	}
);

it(
	'sends a report email when there are failed transactions',
	function () {
		$factory = function ($args ) {
			return new class($args) {
				public $posts = array();
				public function __construct($args ) {
					$statuses    = (array) $args['post_status'];
					$all_posts   = array(
						(object) array(
							'ID'          => 1,
							'post_type'   => 'owc_zgw_transaction',
							'post_status' => 'transaction_success',
						),
						(object) array(
							'ID'          => 2,
							'post_type'   => 'owc_zgw_transaction',
							'post_status' => 'transaction_failed',
						),
					);
					$this->posts = array_filter( $all_posts, fn($p ) => in_array( $p->post_status, $statuses, true ) );
				}
				public function have_posts() {
					return ! empty( $this->posts ); }
			};
		};

		$mailer = new TransactionMailer( $factory );
		$mailer->send_report( 'default@example.com', 'recipient@example.com' );

		expect( $this->sent_mail )->not->toBeNull()
		->and( $this->sent_mail['to'] )->toBe( 'recipient@example.com' )
		->and( $this->sent_mail['subject'] )->toContain( 'Zaaksysteem Failed Transactions Report' )
		->and( $this->sent_mail['message'] )->toContain( 'transaction_failed' )
		->and( $this->sent_mail['message'] )->not->toContain( 'transaction_success' );
	}
);
