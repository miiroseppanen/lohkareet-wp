<?php
/**
 * Event data helpers.
 *
 * Dates are stored as separate year, month, and day values. Empty means unknown.
 * Nothing in this file turns a partial date into a fake exact date.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Municipalities in the Lohkareet series, in program order.
 *
 * @return string[]
 */
function lohkareet_get_municipalities() {
	$municipalities = array(
		'Punkalaidun',
		'Nokia',
		'Sastamala',
		'Hämeenkyrö',
		'Ylöjärvi',
		'Valkeakoski',
		'Pirkkala',
		'Lempäälä',
		'Pälkäne',
	);

	/**
	 * Filters the municipality choices shown in the event editor.
	 *
	 * @param string[] $municipalities Municipality names.
	 */
	$municipalities = apply_filters( 'lohkareet_municipalities', $municipalities );

	return array_values( array_filter( array_map( 'strval', (array) $municipalities ) ) );
}

/**
 * Reads a scalar event meta value.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Meta key.
 */
function lohkareet_get_event_meta_string( $post_id, $key ) {
	$value = get_post_meta( $post_id, $key, true );

	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}

	return trim( (string) $value );
}

/**
 * Reads a positive integer meta value. Zero means the part is unknown.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Meta key.
 */
function lohkareet_get_event_int( $post_id, $key ) {
	return absint( lohkareet_get_event_meta_string( $post_id, $key ) );
}

/**
 * Date format for one precision.
 *
 * Finnish keeps a complete date numeric, so 1 November 2026 is 1.11.2026.
 * English uses the month name: 1 November 2026. Partial dates use the month
 * name in both languages, because a missing part must not be filled with a zero.
 *
 * @param string $precision day-month-year, month-year, or day-month.
 */
function lohkareet_event_date_format( $precision ) {
	if ( 'day-month-year' === $precision ) {
		return _x( 'j F Y', 'event date format: day, month, and year', 'lohkareet' );
	}

	if ( 'day-month' === $precision ) {
		return _x( 'j F', 'event date format: day and month', 'lohkareet' );
	}

	return _x( 'F Y', 'event date format: month and year', 'lohkareet' );
}

/**
 * Human-readable date for an event, or an empty string when the date is unknown.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_date_display( $post_id ) {
	$year  = lohkareet_get_event_int( $post_id, 'lohkareet_date_year' );
	$month = lohkareet_get_event_int( $post_id, 'lohkareet_date_month' );
	$day   = lohkareet_get_event_int( $post_id, 'lohkareet_date_day' );

	if ( $year && $month && $day ) {
		$date = lohkareet_make_date( $year, $month, $day );
		return $date ? lohkareet_format_event_timestamp( $date, 'day-month-year' ) : '';
	}

	if ( $year && $month ) {
		$date = lohkareet_make_date( $year, $month, 1 );
		return $date ? lohkareet_format_event_timestamp( $date, 'month-year' ) : '';
	}

	if ( $year ) {
		return (string) $year;
	}

	if ( $month && $day ) {
		$date = lohkareet_make_date( 2024, $month, $day );
		return $date ? lohkareet_format_event_timestamp( $date, 'day-month' ) : '';
	}

	return '';
}

/**
 * Formats a date in the current locale. The format string never includes a year
 * that the event does not have.
 *
 * @param DateTimeImmutable $date      Calendar date. Its year is ignored when the format has no Y.
 * @param string            $precision day-month-year, month-year, or day-month.
 */
function lohkareet_format_event_timestamp( $date, $precision ) {
	$formatted = wp_date( lohkareet_event_date_format( $precision ), $date->getTimestamp(), wp_timezone() );

	return is_string( $formatted ) ? $formatted : '';
}

/**
 * Machine-readable date for a time element, only when it does not invent a missing part.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_datetime_attr( $post_id ) {
	$year  = lohkareet_get_event_int( $post_id, 'lohkareet_date_year' );
	$month = lohkareet_get_event_int( $post_id, 'lohkareet_date_month' );
	$day   = lohkareet_get_event_int( $post_id, 'lohkareet_date_day' );

	if ( $year && $month && $day ) {
		return sprintf( '%04d-%02d-%02d', $year, $month, $day );
	}

	if ( $year && $month ) {
		return sprintf( '%04d-%02d', $year, $month );
	}

	if ( $year ) {
		return (string) $year;
	}

	return '';
}

/**
 * Date markup, or an empty string when the date is unknown.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_date_html( $post_id ) {
	$text = lohkareet_get_event_date_display( $post_id );

	if ( '' === $text ) {
		return '';
	}

	$datetime = lohkareet_get_event_datetime_attr( $post_id );

	if ( '' === $datetime ) {
		return esc_html( $text );
	}

	return '<time datetime="' . esc_attr( $datetime ) . '">' . esc_html( $text ) . '</time>';
}

/**
 * Time markup for the public site, or an empty string when both times are unknown.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_time_html( $post_id ) {
	$start = lohkareet_get_event_meta_string( $post_id, 'lohkareet_start_time' );
	$end   = lohkareet_get_event_meta_string( $post_id, 'lohkareet_end_time' );

	if ( '' === $start && '' === $end ) {
		return '';
	}

	$start_html = '' !== $start ? '<time datetime="' . esc_attr( $start ) . '">' . esc_html( $start ) . '</time>' : '';
	$end_html   = '' !== $end ? '<time datetime="' . esc_attr( $end ) . '">' . esc_html( $end ) . '</time>' : '';

	if ( '' !== $start && '' !== $end ) {
		return $start_html . '–' . $end_html;
	}

	return $start_html . $end_html;
}

/**
 * Whether the event has a known year and can be placed on a timeline.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_event_has_year( $post_id ) {
	return lohkareet_get_event_int( $post_id, 'lohkareet_date_year' ) > 0;
}

/**
 * Whether an event is certainly in the past.
 *
 * A month or year without a day stays current until that month or year has ended.
 * An event with no year is never treated as past.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_event_is_past( $post_id ) {
	$year  = lohkareet_get_event_int( $post_id, 'lohkareet_date_year' );
	$month = lohkareet_get_event_int( $post_id, 'lohkareet_date_month' );
	$day   = lohkareet_get_event_int( $post_id, 'lohkareet_date_day' );
	$now   = new DateTimeImmutable( 'now', wp_timezone() );

	if ( ! $year ) {
		return false;
	}

	if ( $month && $day ) {
		$event_day = lohkareet_make_date( $year, $month, $day );

		if ( ! $event_day ) {
			return false;
		}

		$today = new DateTimeImmutable( 'today', wp_timezone() );

		if ( $event_day < $today ) {
			return true;
		}

		if ( $event_day > $today ) {
			return false;
		}

		$end = lohkareet_get_event_meta_string( $post_id, 'lohkareet_end_time' );

		if ( ! preg_match( '/^\d{2}:\d{2}$/', $end ) ) {
			return false;
		}

		$end_today = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $today->format( 'Y-m-d' ) . ' ' . $end, wp_timezone() );

		return $end_today instanceof DateTimeImmutable && $end_today < $now;
	}

	$current_year  = (int) $now->format( 'Y' );
	$current_month = (int) $now->format( 'n' );

	if ( $month ) {
		return $year < $current_year || ( $year === $current_year && $month < $current_month );
	}

	return $year < $current_year;
}

/**
 * Sort tuple. Missing months and days use sentinels that exist only here.
 *
 * A year-only event sorts after the dated events of that year.
 * A month without a day sorts after the exact dates in that month.
 * Events with no year sort after dated events. Fully unknown dates sort last.
 *
 * @param int $post_id Event ID.
 * @return int[]
 */
function lohkareet_event_sort_tuple( $post_id ) {
	$year  = lohkareet_get_event_int( $post_id, 'lohkareet_date_year' );
	$month = lohkareet_get_event_int( $post_id, 'lohkareet_date_month' );
	$day   = lohkareet_get_event_int( $post_id, 'lohkareet_date_day' );

	if ( $year ) {
		$sort_month = $month ? $month : 13;
		$sort_day   = ( $month && $day ) ? $day : ( $month ? 32 : 0 );

		return array( 0, $year, $sort_month, $sort_day, $post_id );
	}

	if ( $month || $day ) {
		return array( 1, 0, $month, $day, $post_id );
	}

	return array( 2, 0, 0, 0, $post_id );
}

/**
 * Compares two event posts chronologically.
 *
 * @param WP_Post $a First event.
 * @param WP_Post $b Second event.
 */
function lohkareet_compare_event_posts( $a, $b ) {
	return lohkareet_event_sort_tuple( $a->ID ) <=> lohkareet_event_sort_tuple( $b->ID );
}

/**
 * Published events in chronological order.
 *
 * With Polylang active, the query is limited to the current language slug so
 * a Finnish and English version of the same event are not both listed.
 * suppress_filters stays off so Polylang's query filters can run. Without
 * Polylang the query returns every published event.
 *
 * @return WP_Post[]
 */
function lohkareet_get_events() {
	static $cache = array();

	$language  = lohkareet_get_event_query_language();
	$cache_key = '' !== $language ? $language : 'current';

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$args = array(
		'post_type'           => 'event',
		'post_status'         => 'publish',
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'suppress_filters'    => false,
	);

	if ( '' !== $language ) {
		$args['lang'] = $language;
	}

	$query = new WP_Query( $args );

	$events = $query->posts;
	update_post_thumbnail_cache( $query );
	usort( $events, 'lohkareet_compare_event_posts' );

	$cache[ $cache_key ] = $events;

	return $events;
}

/**
 * The next event that can be placed in the future, or null.
 *
 * @return WP_Post|null
 */
function lohkareet_get_next_event() {
	foreach ( lohkareet_get_events() as $event ) {
		if ( lohkareet_event_has_year( $event->ID ) && ! lohkareet_event_is_past( $event->ID ) ) {
			return $event;
		}
	}

	return null;
}

/**
 * Builds a timezone-aware date, or null when the calendar date is impossible.
 *
 * @param int $year  Year.
 * @param int $month Month.
 * @param int $day   Day.
 * @return DateTimeImmutable|null
 */
function lohkareet_make_date( $year, $month, $day ) {
	$date = DateTimeImmutable::createFromFormat(
		'!Y-m-d',
		sprintf( '%04d-%02d-%02d', $year, $month, $day ),
		wp_timezone()
	);

	if ( ! $date instanceof DateTimeImmutable ) {
		return null;
	}

	$errors = DateTimeImmutable::getLastErrors();

	if ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) {
		return null;
	}

	return $date->setTime( 0, 0 );
}

/**
 * Responsive map iframe. The URL must already be an https URL.
 *
 * @param string $url   Embed URL.
 * @param string $title Accessible name.
 */
function lohkareet_get_map_iframe( $url, $title ) {
	if ( '' === $url ) {
		return '';
	}

	return sprintf(
		'<iframe class="lohkareet-map__frame" title="%1$s" src="%2$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; geolocation" allowfullscreen></iframe>',
		esc_attr( $title ),
		esc_url( $url )
	);
}
