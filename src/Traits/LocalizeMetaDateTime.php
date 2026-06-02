<?php

declare(strict_types=1);

/**
 * Localize DateTime from post meta trait.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   1.13.0
 */

namespace OWCGravityFormsZGW\Traits;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCGravityFormsZGW\Services\DateTimeFormatService;

/**
 * Localize DateTime from post meta trait.
 *
 * @since 1.13.0
 */
trait LocalizeMetaDateTime
{
	private function localize_meta_datetime( int $post_id, string $meta_key ): string
	{
		$date_time = (string) get_post_meta( $post_id, $meta_key, true );
		$formatted = DateTimeFormatService::utc_localized_date_time( $date_time );

		return false !== $formatted ? $formatted : '-';
	}
}
