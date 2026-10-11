<?php
/**
 * Dynamic blocks for the map and event views.
 *
 * The front end renders in PHP. The editor script only previews these blocks.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Lohkareet blocks and their editor preview script.
 */
function lohkareet_register_blocks() {
	register_block_type(
		'lohkareet/zoneatlas-map',
		array(
			'api_version'     => 3,
			'title'           => __( 'Lohkareet map', 'lohkareet' ),
			'category'        => 'lohkareet',
			'attributes'      => array(
				'url'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'title' => array(
					'type'    => 'string',
					'default' => 'Lohkareet',
				),
			),
			'render_callback' => 'lohkareet_render_zoneatlas_map',
		)
	);

	register_block_type(
		'lohkareet/next-event',
		array(
			'api_version'     => 3,
			'title'           => __( 'Next event', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_next_event',
		)
	);

	register_block_type(
		'lohkareet/event-list',
		array(
			'api_version'     => 3,
			'title'           => __( 'Event list', 'lohkareet' ),
			'category'        => 'lohkareet',
			'attributes'      => array(
				'showHeading' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'render_callback' => 'lohkareet_render_event_list',
		)
	);

	register_block_type(
		'lohkareet/language-switcher',
		array(
			'api_version'     => 3,
			'title'           => __( 'Language switcher', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_language_switcher',
		)
	);

	register_block_type(
		'lohkareet/event-details',
		array(
			'api_version'     => 3,
			'title'           => __( 'Event details', 'lohkareet' ),
			'category'        => 'lohkareet',
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'lohkareet_render_event_details',
		)
	);

	wp_register_script(
		'lohkareet-blocks-editor',
		get_theme_file_uri( 'assets/js/blocks-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'init', 'lohkareet_register_blocks' );

/**
 * Adds the Lohkareet block category.
 *
 * @param array[] $categories Existing categories.
 * @return array[]
 */
function lohkareet_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'lohkareet',
			'title' => __( 'Lohkareet', 'lohkareet' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'lohkareet_block_category' );

/**
 * Loads the editor preview for Lohkareet blocks.
 */
function lohkareet_enqueue_block_editor_assets() {
	wp_enqueue_script( 'lohkareet-blocks-editor' );
	lohkareet_inject_editor_translations();
}
add_action( 'enqueue_block_editor_assets', 'lohkareet_enqueue_block_editor_assets' );

/**
 * Renders the series map.
 *
 * @param array $attributes Block attributes.
 */
function lohkareet_render_zoneatlas_map( $attributes, $content = '', $block = null ) {
	unset( $content, $block );
	$url = isset( $attributes['url'] ) ? lohkareet_with_cooperative_gestures( $attributes['url'] ) : '';

	if ( '' === $url ) {
		$url = lohkareet_get_zoneatlas_map_url();
	}

	$title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';

	if ( '' === $title ) {
		$title = __( 'Lohkareet', 'lohkareet' );
	}

	$iframe = lohkareet_get_map_iframe( $url, $title );

	if ( '' === $iframe ) {
		return '';
	}

	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-map' ) ) . '>' . $iframe . '</div>';
}

/**
 * Renders the next upcoming event.
 */
function lohkareet_render_next_event( $attributes = array(), $content = '', $block = null ) {
	unset( $attributes, $content, $block );
	$heading_id = wp_unique_id( 'lohkareet-next-event-' );
	$event      = lohkareet_get_next_event();

	$html  = '<section ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-next-event' ) ) . ' aria-labelledby="' . esc_attr( $heading_id ) . '">';
	$html .= '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html__( 'Next event', 'lohkareet' ) . '</h2>';

	if ( ! $event instanceof WP_Post ) {
		$html .= '<p>' . esc_html__( 'The next date will be announced later.', 'lohkareet' ) . '</p>';
		$html .= '</section>';
		return $html;
	}

	$html .= lohkareet_get_event_card_html( $event, 3 );
	$html .= '</section>';

	return $html;
}

/**
 * Renders every published event.
 *
 * @param array $attributes Block attributes.
 */
function lohkareet_render_event_list( $attributes, $content = '', $block = null ) {
	unset( $content, $block );
	$events       = lohkareet_get_events();
	$show_heading = ! empty( $attributes['showHeading'] );
	$heading_id   = wp_unique_id( 'lohkareet-event-list-' );

	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'lohkareet-event-list-block',
		)
	);

	$html = $show_heading ? '<section ' . $wrapper . ' aria-labelledby="' . esc_attr( $heading_id ) . '">' : '<div ' . $wrapper . '>';

	if ( $show_heading ) {
		$html .= '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html__( 'Events', 'lohkareet' ) . '</h2>';
	}

	if ( ! $events ) {
		$html .= '<p>' . esc_html__( 'No events have been published yet.', 'lohkareet' ) . '</p>';
		$html .= $show_heading ? '</section>' : '</div>';
		return $html;
	}

	$html .= '<ul class="lohkareet-event-list">';

	$heading_level = $show_heading ? 3 : 2;

	foreach ( $events as $event ) {
		$html .= '<li class="lohkareet-event-card">' . lohkareet_get_event_card_html( $event, $heading_level ) . '</li>';
	}

	$html .= '</ul>';
	$html .= $show_heading ? '</section>' : '</div>';

	return $html;
}

/**
 * Renders one event card.
 *
 * @param WP_Post $event         Event post.
 * @param int     $heading_level Heading level, 2 or 3.
 */
function lohkareet_get_event_card_html( $event, $heading_level ) {
	$heading_level = 3 === $heading_level ? 3 : 2;
	$post_id       = $event->ID;
	$permalink     = get_permalink( $event );

	if ( ! is_string( $permalink ) || '' === $permalink ) {
		return '';
	}

	$municipality = lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' );
	$title        = get_the_title( $event );

	if ( '' === $title ) {
		$title = '' !== $municipality ? $municipality : __( 'Event', 'lohkareet' );
	}
	$venue         = lohkareet_get_event_meta_string( $post_id, 'lohkareet_venue' );
	$date_html     = lohkareet_get_event_date_html( $post_id );
	$time_html     = lohkareet_get_event_time_html( $post_id );
	$thumbnail_id  = get_post_thumbnail_id( $post_id );

	if ( '' === $date_html ) {
		$date_html = esc_html__( 'The date will be announced later.', 'lohkareet' );
	}

	$html = '<article class="lohkareet-event-summary">';

	if ( $thumbnail_id ) {
		$html .= wp_get_attachment_image(
			$thumbnail_id,
			'medium_large',
			false,
			array(
				'class' => 'lohkareet-event-card__image',
			)
		);
	}

	$html .= '<h' . $heading_level . '><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h' . $heading_level . '>';

	if ( '' !== $municipality && $municipality !== $title ) {
		$html .= '<p>' . esc_html( $municipality ) . '</p>';
	}

	$html .= '<p>' . $date_html . '</p>';

	if ( '' !== $time_html ) {
		$html .= '<p>' . $time_html . '</p>';
	}

	if ( '' !== $venue ) {
		$html .= '<p>' . esc_html( $venue ) . '</p>';
	}

	$html .= '</article>';

	return $html;
}

/**
 * Renders the facts for a single event. Empty facts are omitted.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 */
function lohkareet_render_event_details( $attributes, $content, $block ) {
	unset( $attributes, $content );

	$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_queried_object_id();

	if ( ! $post_id || 'event' !== get_post_type( $post_id ) ) {
		return '';
	}

	$municipality = lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' );
	$venue        = lohkareet_get_event_meta_string( $post_id, 'lohkareet_venue' );
	$address      = lohkareet_get_event_meta_string( $post_id, 'lohkareet_address' );
	$location     = lohkareet_get_event_meta_string( $post_id, 'lohkareet_location_description' );
	$map_url      = lohkareet_sanitize_https_url( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_url' ) );
	$artist       = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_name' );
	$artist_text  = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_description' );
	$status       = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_status' );
	$date_html    = lohkareet_get_event_date_html( $post_id );
	$time_html    = lohkareet_get_event_time_html( $post_id );

	if ( '' === $status ) {
		$status = 'unannounced';
	}

	$html = '<div ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-event-facts' ) ) . '>';

	$event_title = get_the_title( $post_id );

	if ( '' !== $municipality && $municipality !== $event_title ) {
		$html .= '<p>' . esc_html( $municipality ) . '</p>';
	}

	$html .= '<p>' . ( '' !== $date_html ? $date_html : esc_html__( 'The date will be announced later.', 'lohkareet' ) ) . '</p>';

	if ( '' !== $time_html ) {
		$html .= '<p>' . $time_html . '</p>';
	}

	$html .= '<p>' . ( '' !== $venue ? esc_html( $venue ) : esc_html__( 'The venue will be announced later.', 'lohkareet' ) ) . '</p>';

	if ( '' !== $address ) {
		$html .= '<p>' . esc_html( $address ) . '</p>';
	}

	if ( '' !== $location ) {
		$html .= '<p>' . nl2br( esc_html( $location ), false ) . '</p>';
	}

	if ( '' !== $map_url ) {
		$map_title = sprintf(
			/* translators: %s: event title. */
			__( 'Map: %s', 'lohkareet' ),
			get_the_title( $post_id )
		);
		$iframe = lohkareet_get_map_iframe( $map_url, $map_title );

		if ( '' !== $iframe ) {
			$html .= '<div class="lohkareet-map">' . $iframe . '</div>';
		}
	}

	if ( 'unannounced' === $status || '' === $artist ) {
		$html .= '<p>' . esc_html__( 'The artist will be announced later.', 'lohkareet' ) . '</p>';
	} else {
		$html .= '<p>' . esc_html( $artist ) . '</p>';

		if ( 'tentative' === $status ) {
			$html .= '<p>' . esc_html__( 'Tentative, not yet confirmed.', 'lohkareet' ) . '</p>';
		}

		if ( '' !== $artist_text ) {
			$html .= '<p>' . nl2br( esc_html( $artist_text ), false ) . '</p>';
		}
	}

	$html .= '</div>';

	return $html;
}
