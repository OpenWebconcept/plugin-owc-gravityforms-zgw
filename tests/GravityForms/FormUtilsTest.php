<?php

use OWCGravityFormsZGW\GravityForms\FormUtils;

it(
	'awaits payment until the entry is paid',
	function ( ?string $payment_status, bool $awaits_payment ) {
		expect( FormUtils::entry_is_unpaid( array( 'payment_status' => $payment_status ) ) )->toBe( $awaits_payment );
	}
)->with(
	array(
		'no payment'                    => array( null, false ),
		'empty payment status'          => array( '', false ),
		'processing'                    => array( 'Processing', true ),
		'pending'                       => array( 'Pending', true ),
		'failed while starting payment' => array( 'Failed', true ),
		'cancelled'                     => array( 'Cancelled', true ),
		'expired'                       => array( 'Expired', true ),
		'paid'                          => array( 'Paid', false ),
	)
);

it(
	'does not await payment for an entry without payment fields',
	function () {
		expect( FormUtils::entry_is_unpaid( array( 'id' => 1 ) ) )->toBeFalse();
	}
);
