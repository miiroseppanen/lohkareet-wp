<?php
/**
 * Photographs from the visits, credited to the photographer named in the files.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The published set. Dates, places, and the photographer come from the filenames.
 *
 * @return array<string, array<string, mixed>>
 */
function lohkareet_visit_photos() {
	return array(
		'punkalaidun-4'  => array(
			'file'   => '2026-11-01 Lohkareet, Punkalaidun, Henrik Laakkonen, 4.jpg',
			'event'  => 'Punkalaidun',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'A person at a table of equipment in the forest, with visitors and small lights behind, at Lohkareet in Punkalaidun.', 'lohkareet' ),
		),
		'punkalaidun-9'  => array(
			'file'   => '2026-11-01 Lohkareet, Punkalaidun, Henrik Laakkonen, 9.jpg',
			'event'  => 'Punkalaidun',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'People sitting and standing in a dark forest among small lights at Lohkareet in Punkalaidun.', 'lohkareet' ),
		),
		'punkalaidun-12' => array(
			'file'   => '2026-11-01 Lohkareet, Punkalaidun, Henrik Laakkonen, 12.jpg',
			'event'  => 'Punkalaidun',
			'width'  => 1536,
			'height' => 1920,
			'alt'    => __( 'People and a dog on mossy ground in the forest at Lohkareet in Punkalaidun.', 'lohkareet' ),
		),
		'punkalaidun-27' => array(
			'file'   => '2026-11-01 Lohkareet, Punkalaidun, Henrik Laakkonen, 27.jpg',
			'event'  => 'Punkalaidun',
			'width'  => 1280,
			'height' => 1920,
			'shift'  => true,
			'alt'    => __( 'A tree stump and grass lit red in a dark forest at Lohkareet in Punkalaidun.', 'lohkareet' ),
		),
		'nokia-2'        => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 2.jpg',
			'event'  => 'Nokia',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'Two people on a snowy forest path beside a sign that reads Tervetuloa metsään, at Lohkareet in Nokia.', 'lohkareet' ),
		),
		'nokia-4'        => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 4.jpg',
			'event'  => 'Nokia',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'Coloured light projected across a mossy boulder in a dark forest at Lohkareet in Nokia.', 'lohkareet' ),
		),
		'nokia-5'        => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 5.jpg',
			'event'  => 'Nokia',
			'width'  => 1280,
			'height' => 1920,
			'shift'  => true,
			'alt'    => __( 'A mossy boulder lit red between trees at Lohkareet in Nokia.', 'lohkareet' ),
		),
		'nokia-6'        => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 6.jpg',
			'event'  => 'Nokia',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'Visitors in a forest beside a boulder lit red at Lohkareet in Nokia.', 'lohkareet' ),
		),
		'nokia-11'       => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 11.jpg',
			'event'  => 'Nokia',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'A musician playing flute and keyboard among the trees at Lohkareet in Nokia.', 'lohkareet' ),
		),
		'nokia-16'       => array(
			'file'   => '2026-11-15 Lohkareet, Nokia, Henrik Laakkonen, 16.jpg',
			'event'  => 'Nokia',
			'width'  => 1920,
			'height' => 1280,
			'alt'    => __( 'A crowd in the forest facing a small stage, with a boulder lit behind them, at Lohkareet in Nokia.', 'lohkareet' ),
		),
	);
}

/**
 * One photograph with the photographer and the event in the lower right.
 *
 * @param string $key     Catalog key.
 * @param string $variant Extra class suffix, such as bridge or preparation.
 */
function lohkareet_get_visit_figure_html( $key, $variant = '' ) {
	$photos = lohkareet_visit_photos();

	if ( ! isset( $photos[ $key ] ) ) {
		return '';
	}

	$photo = $photos[ $key ];
	$path  = get_theme_file_path( 'assets/images/henrik_laakkonen/' . $photo['file'] );

	if ( ! is_readable( $path ) ) {
		return '';
	}

	$url       = get_theme_file_uri( 'assets/images/henrik_laakkonen/' . $photo['file'] );
	$portrait  = (int) $photo['height'] > (int) $photo['width'];
	$classes   = array( 'lohkareet-visit', $portrait ? 'lohkareet-visit--portrait' : 'lohkareet-visit--landscape' );

	if ( '' !== $variant ) {
		$classes[] = 'lohkareet-visit--' . sanitize_html_class( $variant );
	}

	if ( $portrait && ! empty( $photo['shift'] ) ) {
		$classes[] = 'lohkareet-visit--shift';
	}

	$credit = 'Henrik Laakkonen · ' . $photo['event'];

	return sprintf(
		'<figure class="%1$s"><img src="%2$s" alt="%3$s" width="%4$d" height="%5$d" decoding="async" loading="lazy"><figcaption class="lohkareet-visit__credit">%6$s</figcaption></figure>',
		esc_attr( implode( ' ', $classes ) ),
		esc_url( $url ),
		esc_attr( $photo['alt'] ),
		(int) $photo['width'],
		(int) $photo['height'],
		esc_html( $credit )
	);
}

/**
 * A loose sequence of visit photographs.
 *
 * @param string[] $keys     Catalog keys, in display order.
 * @param string   $modifier Gallery modifier, such as home or archive.
 */
function lohkareet_get_visit_gallery_html( $keys, $modifier = '' ) {
	$figures = '';

	foreach ( $keys as $key ) {
		$figures .= lohkareet_get_visit_figure_html( $key );
	}

	if ( '' === $figures ) {
		return '';
	}

	$class = 'lohkareet-visits';

	if ( '' !== $modifier ) {
		$class .= ' lohkareet-visits--' . sanitize_html_class( $modifier );
	}

	return '<div class="' . esc_attr( $class ) . '">' . $figures . '</div>';
}

/**
 * Appends the visit gallery after the front-page text.
 *
 * @param string $content Rendered page content.
 */
function lohkareet_append_front_visit_gallery( $content ) {
	if ( ! is_string( $content ) || is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( str_contains( $content, 'lohkareet-visits' ) ) {
		return $content;
	}

	$keys = array(
		'punkalaidun-4',
		'punkalaidun-12',
		'punkalaidun-27',
		'nokia-11',
	);

	return $content . lohkareet_get_visit_gallery_html( $keys, 'home' );
}
add_filter( 'the_content', 'lohkareet_append_front_visit_gallery', 14 );

/**
 * One photograph at the end of the preparation page.
 *
 * @param string $content Rendered page content.
 */
function lohkareet_append_preparation_visit_photo( $content ) {
	if ( ! is_string( $content ) || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type || ! function_exists( 'lohkareet_is_preparation_post' ) || ! lohkareet_is_preparation_post( $post ) ) {
		return $content;
	}

	if ( str_contains( $content, 'lohkareet-visit--preparation' ) ) {
		return $content;
	}

	return $content . lohkareet_get_visit_figure_html( 'nokia-2', 'preparation' );
}
add_filter( 'the_content', 'lohkareet_append_preparation_visit_photo', 13 );

/**
 * Photograph for a work, when one of the visit pictures belongs to it.
 *
 * QODOVV uses the Nokia boulder projection. The Lindgren sound work uses
 * the red-lit boulder with visitors. The credit still names the Nokia visit.
 *
 * @param int $work_id Work ID.
 */
function lohkareet_get_work_visit_figure_html( $work_id ) {
	$names = function_exists( 'lohkareet_work_contributor_names' ) ? lohkareet_work_contributor_names( $work_id ) : array();
	$title = function_exists( 'lohkareet_translated_meta_string' ) ? lohkareet_translated_meta_string( $work_id, 'lohkareet_work_title' ) : '';
	$label = strtolower( remove_accents( implode( ' ', $names ) . ' ' . $title . ' ' . get_the_title( $work_id ) ) );

	if ( str_contains( $label, 'qodovv' ) ) {
		return lohkareet_get_visit_figure_html( 'nokia-4', 'work' );
	}

	if ( str_contains( $label, 'lindgren' ) ) {
		return lohkareet_get_visit_figure_html( 'nokia-6', 'work' );
	}

	return '';
}

/**
 * A few photographs after the event archive list.
 *
 * @param string $html Event list markup.
 */
function lohkareet_event_list_with_visits( $html ) {
	if ( ! is_post_type_archive( 'event' ) ) {
		return $html;
	}

	$keys = array( 'punkalaidun-9', 'nokia-5' );

	return $html . lohkareet_get_visit_gallery_html( $keys, 'archive' );
}
