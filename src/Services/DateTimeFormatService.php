<?php

declare(strict_types=1);

/**
 * DateTime Format Service.
 *
 * @package OWC_GravityForms_ZGW
 * @author  Yard | Digital Agency
 * @since   NEXT
 */

namespace OWCGravityFormsZGW\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;
use DateTimeZone;
use Exception;

/**
 * DateTime Format Service.
 *
 * @since NEXT
 */
class DateTimeFormatService
{
	public static function utc_localized_date_time( string $date_time_string ): string
	{
		if ( '' === $date_time_string ) {
			return '';
		}

		try {
			$date_time = new DateTime( $date_time_string, new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			return $date_time_string;
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date_time->getTimestamp() );
	}
}
