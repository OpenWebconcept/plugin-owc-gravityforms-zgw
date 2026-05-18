<?php

declare(strict_types=1);

/**
 * Entry note trait.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.9.0
 */

namespace OWCGravityFormsZGW\GravityForms\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFAPI;

/**
 * Entry note trait.
 *
 * @since 1.9.0
 */
trait EntryNote
{
	public function add_entry_note( int|string $entry_id, string $message, string $type = 'error' ): void
	{
		GFAPI::add_note( $entry_id, 0, \OWC_GRAVITYFORMS_ZGW_PLUGIN_NAME, $message, 'user', $type );
	}
}
