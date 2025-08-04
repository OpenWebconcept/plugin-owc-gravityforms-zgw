<?php
/**
 * Transaction Status.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCGravityFormsZGW\Transactions;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Transaction Status.
 *
 * @since 1.0.0
 */
class TransactionStatus
{
	const SUCCESS = 'Success';

	const PENDING = 'Pending';

	const FAILED = 'Failed';
}
