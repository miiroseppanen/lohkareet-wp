<?php
/**
 * Front-page hero.
 *
 * Video:    theme/assets/video/lohkare_animaatio.mp4
 * Fallback: theme/assets/images/lohkare_animaatio-fallback.jpg
 * Logo:     theme/assets/images/siirtyma-Eventimage-profilelogo-white.svg
 *
 * Both are theme files. They are not editable fields and they are not copied
 * elsewhere. The logo is the original white Siirtymä mark.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public URI for a theme file, or an empty string when the file is absent.
 *
 * @param string $relative Path relative to the theme root.
 */
function lohkareet_theme_asset_uri( $relative ) {
	$path = get_theme_file_path( $relative );

	if ( ! is_string( $path ) || ! is_readable( $path ) ) {
		return '';
	}

	return get_theme_file_uri( $relative );
}

/**
 * Registers the hero block and its assets.
 */
function lohkareet_register_hero() {
	register_block_type(
		'lohkareet/hero',
		array(
			'api_version'     => 3,
			'title'           => __( 'Hero', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_hero',
		)
	);

	wp_register_style(
		'lohkareet-hero',
		get_theme_file_uri( 'assets/css/hero.css' ),
		array( 'lohkareet-typekit' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_register_script(
		'lohkareet-hero-video',
		get_theme_file_uri( 'assets/js/hero-video.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_register_script(
		'lohkareet-hero-events',
		get_theme_file_uri( 'assets/js/hero-events.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'init', 'lohkareet_register_hero' );

/**
 * Loads hero CSS in the block editor canvas.
 */
function lohkareet_hero_editor_style() {
	add_editor_style( 'assets/css/hero.css' );
}
add_action( 'after_setup_theme', 'lohkareet_hero_editor_style' );

/**
 * Renders the front-page hero.
 *
 * @param array $attributes Block attributes.
 */
function lohkareet_render_hero( $attributes = array(), $content = '', $block = null ) {
	unset( $attributes, $content, $block );

	wp_enqueue_style( 'lohkareet-hero' );

	$events = lohkareet_get_hero_event_items();
	$cycle  = count( $events ) > 1 && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );

	if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		wp_enqueue_script( 'lohkareet-hero-video' );

		if ( $cycle ) {
			wp_enqueue_script( 'lohkareet-hero-events' );
		}
	}

	$video    = lohkareet_theme_asset_uri( 'assets/video/lohkare_animaatio.mp4' );
	$fallback = lohkareet_theme_asset_uri( 'assets/images/lohkare_animaatio-fallback.jpg' );
	$logo     = lohkareet_theme_asset_uri( 'assets/images/siirtyma-Eventimage-profilelogo-white.svg' );

	$classes = 'lohkareet-hero';

	if ( '' === $video ) {
		$classes .= ' is-video-missing';
	}

	$html = '<div ' . get_block_wrapper_attributes( array( 'class' => $classes ) ) . '>';

	if ( '' !== $video ) {
		// The source stays off the element until the fallback has loaded.
		$html .= '<div class="lohkareet-hero__picture">';
		$html .= '<video class="lohkareet-hero__video" muted playsinline preload="none" disablepictureinpicture aria-hidden="true" tabindex="-1" data-src="' . esc_url( $video ) . '"></video>';
		$html .= '</div>';
	}

	if ( '' !== $fallback ) {
		$html .= '<img class="lohkareet-hero__fallback" src="' . esc_url( $fallback ) . '" alt="" width="1386" height="1920" decoding="async" fetchpriority="high">';
	}

	$html .= '<div class="lohkareet-hero__shade" aria-hidden="true"></div>';
	$html .= '<div class="lohkareet-hero__overlay">';
	$html .= '<div class="lohkareet-hero__identity">';

	if ( '' !== $logo ) {
		$html .= '<img class="lohkareet-hero__logo" src="' . esc_url( $logo ) . '" alt="' . esc_attr__( 'Siirtymä', 'lohkareet' ) . '" width="550" height="550">';
	}

	$html .= '<p class="lohkareet-hero__title">LOHKAREET</p>';
	$html .= '</div></div>';
	$html .= lohkareet_get_hero_event_cycle_html( $events, $cycle );
	$html .= '</div>';

	return $html;
}

/**
 * Published events that can be shown in the hero cycle.
 *
 * A row needs a complete calendar date, a public artist name, and a
 * municipality. Partial dates stay out, and an unannounced artist is not
 * treated as a name. Polylang limits the list to the current language, so a
 * translation is not a second event. The order is the shared chronological
 * order, with the post ID as the tie-break.
 *
 * @return array<int, array{url: string, date: string, datetime: string, artist: string, municipality: string}>
 */
function lohkareet_get_hero_event_items() {
	$items = array();

	foreach ( lohkareet_get_events() as $event ) {
		if ( ! $event instanceof WP_Post || ! lohkareet_event_has_public_listing_date( $event->ID ) ) {
			continue;
		}

		$artist       = lohkareet_get_event_meta_string( $event->ID, 'lohkareet_artist_name' );
		$status       = lohkareet_get_event_meta_string( $event->ID, 'lohkareet_artist_status' );
		$municipality = lohkareet_get_event_meta_string( $event->ID, 'lohkareet_municipality' );

		if ( '' === $artist || 'unannounced' === $status || '' === $municipality ) {
			continue;
		}

		$url  = get_permalink( $event );
		$date = lohkareet_get_event_date_display( $event->ID );

		if ( ! is_string( $url ) || '' === $url || '' === $date ) {
			continue;
		}

		$items[] = array(
			'url'          => $url,
			'date'         => $date,
			'datetime'     => lohkareet_get_event_datetime_attr( $event->ID ),
			'artist'       => $artist,
			'municipality' => $municipality,
		);
	}

	return $items;
}

/**
 * Hero event cycle, or an empty string when nothing qualifies.
 *
 * Every event stays in the same grid so a later, longer name cannot move the
 * row. One event is shown as-is. Several events wait for the opacity script.
 *
 * @param array<int, array{url: string, date: string, datetime: string, artist: string, municipality: string}> $events Qualifying events.
 * @param bool                                                                                                 $cycle  Whether the public page should advance them.
 */
function lohkareet_get_hero_event_cycle_html( $events, $cycle ) {
	if ( ! $events ) {
		return '';
	}

	$class = 'lohkareet-hero__cycle';

	if ( ! $cycle ) {
		$class .= ' is-static';
	}

	$html = '<div class="lohkareet-hero__events">';
	$html .= '<div class="' . esc_attr( $class ) . '">';

	foreach ( $events as $index => $event ) {
		$current = 0 === $index;
		$attrs   = ' class="lohkareet-hero__event' . ( $current ? ' is-current' : '' ) . '" href="' . esc_url( $event['url'] ) . '"';

		if ( ! $current ) {
			$attrs .= ' tabindex="-1" aria-hidden="true"';
		}

		$time = '<time class="lohkareet-hero__fact lohkareet-hero__fact--date"';

		if ( '' !== $event['datetime'] ) {
			$time .= ' datetime="' . esc_attr( $event['datetime'] ) . '"';
		}

		$time .= '>' . esc_html( $event['date'] ) . '</time>';

		$html .= '<a' . $attrs . '>';
		$html .= $time;
		$html .= '<span class="lohkareet-hero__fact lohkareet-hero__fact--artist">' . esc_html( $event['artist'] ) . '</span>';
		$html .= '<span class="lohkareet-hero__fact lohkareet-hero__fact--municipality">' . esc_html( $event['municipality'] ) . '</span>';
		$html .= '</a>';
	}

	$html .= '</div></div>';

	return $html;
}
