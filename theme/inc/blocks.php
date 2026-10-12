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
			'attributes'      => array(
				'part' => array(
					'type'    => 'string',
					'default' => 'all',
				),
			),
			'render_callback' => 'lohkareet_render_event_details',
		)
	);

	register_block_type(
		'lohkareet/page-hero',
		array(
			'api_version'     => 3,
			'title'           => __( 'Page hero', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_page_hero',
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
 * Black opening field for subpages.
 *
 * The front page keeps its video hero. This block is empty there.
 */
function lohkareet_render_page_hero() {
	if ( is_front_page() ) {
		return '';
	}

	$title  = '';
	$before = '';
	$extra  = '';

	$mark = '';

	if ( is_singular( 'event' ) ) {
		$post_id = get_queried_object_id();
		$title   = get_the_title( $post_id );
		$before  = lohkareet_get_event_hero_when_html( $post_id );
		$extra   = lohkareet_get_event_hero_place_html( $post_id );
		$mark    = lohkareet_get_event_hero_logo_html( $post_id );
	} elseif ( is_singular() ) {
		$title = get_the_title();
	} elseif ( is_post_type_archive( 'event' ) ) {
		$title = post_type_archive_title( '', false );
	} elseif ( is_search() ) {
		$title = sprintf(
			/* translators: %s: search query. */
			__( 'Search results for “%s”', 'lohkareet' ),
			get_search_query( false )
		);
	} elseif ( is_archive() ) {
		$title = wp_strip_all_tags( get_the_archive_title() );
	} elseif ( is_home() && ! is_front_page() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		$title      = $posts_page ? get_the_title( $posts_page ) : '';
	}

	$title = is_string( $title ) ? trim( wp_strip_all_tags( $title ) ) : '';

	if ( '' === $title ) {
		return '';
	}

	$html  = '<section ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-page-hero' ) ) . '>';
	$html .= $mark;
	$html .= '<div class="lohkareet-page-hero__grid editorial-container">';
	$html .= $before;
	$html .= '<h1 class="lohkareet-page-hero__title">' . esc_html( $title ) . '</h1>';
	$html .= $extra;
	$html .= '</div></section>';

	return $html;
}

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

	if ( '' === $url ) {
		return '';
	}

	$iframe = sprintf(
		'<iframe class="lohkareet-map__frame" id="zoneatlas-map-iframe" title="%1$s" width="100" height="600" scrolling="no" frameborder="0" src="%2$s" allow="fullscreen; geolocation"></iframe>',
		esc_attr( $title ),
		esc_url( $url )
	);

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

	$name         = '' !== $municipality ? $municipality : $title;
	$when_html    = lohkareet_get_event_listing_when_html( $post_id );
	$place        = lohkareet_get_event_listing_place( $post_id );
	$artist       = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_name' );
	$status       = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_status' );
	$thumbnail_id = get_post_thumbnail_id( $post_id );

	$html  = '<a class="lohkareet-event-summary" href="' . esc_url( $permalink ) . '">';
	$html .= '<span class="lohkareet-event-summary__body">';

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

	if ( '' !== $when_html ) {
		$html .= '<p class="lohkareet-event-summary__when">' . $when_html . '</p>';
	}

	$html .= '<h' . $heading_level . ' class="lohkareet-event-summary__name">' . esc_html( $name ) . '</h' . $heading_level . '>';

	if ( '' !== $place ) {
		$html .= '<p class="lohkareet-event-summary__place">' . esc_html( $place ) . '</p>';
	}

	if ( 'confirmed' === $status && '' !== $artist ) {
		$html .= '<p class="lohkareet-event-summary__artist">' . esc_html( $artist ) . '</p>';
	}

	$html .= '</span>';
	$html .= '<span class="lohkareet-event-summary__arrow" aria-hidden="true">';
	$html .= '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">';
	$html .= '<path d="M25.5625 4.4375C22.6458 1.47917 19.125 0 15 0C10.875 0 7.34375 1.46875 4.40625 4.40625C1.46875 7.34375 0 10.875 0 15C0 19.125 1.46875 22.6562 4.40625 25.5938C7.34375 28.5312 10.875 30 15 30C19.125 30 22.6562 28.5312 25.5938 25.5938C28.5312 22.6562 30 19.125 30 15C30 10.875 28.5208 7.35417 25.5625 4.4375ZM23.9375 14.625C23.9792 14.75 24 14.875 24 15C24 15.125 23.9792 15.25 23.9375 15.375C23.8958 15.5 23.8125 15.6042 23.6875 15.6875L17.6875 21.6875C17.2292 22.1875 16.7604 22.1979 16.2812 21.7188C15.8021 21.2396 15.8125 20.7708 16.3125 20.3125L20.5625 16H5C4.70833 16 4.46875 15.9062 4.28125 15.7188C4.09375 15.5312 4 15.2917 4 15C4 14.7083 4.09375 14.4688 4.28125 14.2812C4.46875 14.0938 4.70833 14 5 14H20.5625L16.3125 9.6875C15.8125 9.22917 15.8021 8.76042 16.2812 8.28125C16.7604 7.80208 17.2292 7.8125 17.6875 8.3125L23.6875 14.3125C23.8125 14.3958 23.8958 14.5 23.9375 14.625Z" fill="currentColor"/>';
	$html .= '</svg></span>';
	$html .= '</a>';

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
	unset( $content );

	$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_queried_object_id();

	if ( ! $post_id || 'event' !== get_post_type( $post_id ) ) {
		return '';
	}

	$part = isset( $attributes['part'] ) ? (string) $attributes['part'] : 'all';

	if ( ! in_array( $part, array( 'facts', 'programme', 'context', 'all' ), true ) ) {
		$part = 'all';
	}

	$show_facts     = 'all' === $part || 'facts' === $part;
	$show_programme = 'all' === $part || 'programme' === $part;
	$show_context   = 'all' === $part || 'context' === $part;
	$inner          = '';

	if ( $show_facts ) {
		$inner .= lohkareet_get_event_facts_html( $post_id );
	}

	if ( $show_programme ) {
		$inner .= lohkareet_get_event_programme_html( $post_id );
	}

	if ( $show_context ) {
		$inner .= lohkareet_get_event_context_html( $post_id );
	}

	if ( '' === $inner ) {
		return '';
	}

	return '<div ' . get_block_wrapper_attributes(
		array(
			'class' => 'lohkareet-event-facts lohkareet-event-details lohkareet-event-details--' . $part,
		)
	) . '>' . $inner . '</div>';
}

/**
 * Date, time, and place for one event page.
 *
 * The performing artist is part of the programme, not this place-and-time block.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_facts_html( $post_id ) {
	return lohkareet_get_event_hero_when_html( $post_id ) . lohkareet_get_event_hero_place_html( $post_id );
}

/**
 * Municipality mark in the event hero. Empty when the place has no logo.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_hero_logo_html( $post_id ) {
	$logo = lohkareet_get_municipality_logo( lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' ) );

	if ( ! is_array( $logo ) ) {
		return '';
	}

	$class = 'lohkareet-page-hero__mark';

	if ( (int) $logo['height'] > (int) $logo['width'] ) {
		$class .= ' is-stacked';
	}

	return '<div class="' . esc_attr( $class ) . '">' . lohkareet_get_municipality_logo_image( $logo ) . '</div>';
}

/**
 * Date and time shown above the event title.
 *
 * A complete date uses the same compact line as a listing. A partial date
 * stays on its own line, with the time or its placeholder underneath.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_hero_when_html( $post_id ) {
	$listing = lohkareet_get_event_listing_when_html( $post_id );

	if ( '' !== $listing ) {
		$html = '<p class="lohkareet-event-facts__when">' . $listing . '</p>';

		if ( '' === lohkareet_get_event_time_html( $post_id ) ) {
			$html .= '<p class="lohkareet-event-facts__time">' . esc_html__( 'The time will be confirmed.', 'lohkareet' ) . '</p>';
		}

		return $html;
	}

	$date_html = lohkareet_get_event_date_html( $post_id );
	$time_html = lohkareet_get_event_time_html( $post_id );

	$html  = '<p class="lohkareet-event-facts__date">' . ( '' !== $date_html ? $date_html : esc_html__( 'The date will be confirmed.', 'lohkareet' ) ) . '</p>';
	$html .= '<p class="lohkareet-event-facts__time">' . ( '' !== $time_html ? $time_html : esc_html__( 'The time will be confirmed.', 'lohkareet' ) ) . '</p>';

	return $html;
}

/**
 * Place lines shown under the event title.
 *
 * The title already carries the municipality when they are the same name.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_hero_place_html( $post_id ) {
	$municipality = lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' );
	$venue        = lohkareet_get_event_meta_string( $post_id, 'lohkareet_venue' );
	$address      = lohkareet_get_event_meta_string( $post_id, 'lohkareet_address' );
	$event_title  = get_the_title( $post_id );
	$html         = '';

	if ( '' !== $municipality && $municipality !== $event_title ) {
		$html .= '<p class="lohkareet-event-facts__municipality">' . esc_html( $municipality ) . '</p>';
	}

	$html .= '<p class="lohkareet-event-facts__venue">' . ( '' !== $venue ? esc_html( $venue ) : esc_html__( 'The venue will be confirmed.', 'lohkareet' ) ) . '</p>';

	if ( '' !== $address ) {
		$html .= '<p class="lohkareet-event-facts__address">' . esc_html( $address ) . '</p>';
	}

	return $html;
}

/**
 * Performing artist and the works assigned to this event.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_programme_html( $post_id ) {
	$artist = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_name' );
	$status = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_status' );
	$works  = lohkareet_parse_id_list( lohkareet_get_event_meta_string( $post_id, 'lohkareet_work_ids' ) );

	if ( '' === $status ) {
		$status = 'unannounced';
	}

	$show_artist = '' !== $artist && in_array( $status, array( 'confirmed', 'tentative' ), true );
	$html        = '';

	if ( ! $show_artist && ! $works ) {
		return '<p>' . esc_html__( 'The artist will be announced later.', 'lohkareet' ) . '</p>';
	}

	$html .= '<div class="lohkareet-programme">';

	if ( $show_artist ) {
		$html .= lohkareet_get_performing_artist_html( $post_id, $artist, $status );
	} else {
		$html .= '<p>' . esc_html__( 'The artist will be announced later.', 'lohkareet' ) . '</p>';
	}

	foreach ( $works as $work_id ) {
		if ( 'work' !== get_post_type( $work_id ) ) {
			continue;
		}

		$html .= lohkareet_get_work_html( $work_id );
	}

	$html .= '</div>';

	return $html;
}

/**
 * The event’s main performing artist.
 *
 * @param int    $post_id Event ID.
 * @param string $artist  Artist name.
 * @param string $status  confirmed or tentative.
 */
function lohkareet_get_performing_artist_html( $post_id, $artist, $status ) {
	$heading = $artist;

	if ( 'tentative' === $status ) {
		$heading = sprintf(
			/* translators: %s: artist name that is not yet confirmed. */
			__( 'Tentative: %s', 'lohkareet' ),
			$artist
		);
	}

	$html  = '<section class="lohkareet-programme__artist">';
	$html .= '<h2>' . esc_html( $heading ) . '</h2>';

	$image_id = (int) lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_image' );

	if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
		$html .= wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'class' => 'lohkareet-programme__image',
			)
		);
	}

	$description = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_description' );

	if ( '' !== $description ) {
		$html .= '<div class="lohkareet-programme__description"><p>' . nl2br( esc_html( $description ), false ) . '</p></div>';
	}

	$html .= '</section>';

	return $html;
}

/**
 * One assigned work. The contributor name is not translated.
 *
 * @param int $work_id Work ID.
 */
function lohkareet_get_work_html( $work_id ) {
	$names = lohkareet_work_contributor_names( $work_id );
	$type  = lohkareet_translated_meta_string( $work_id, 'lohkareet_work_type' );
	$title = lohkareet_translated_meta_string( $work_id, 'lohkareet_work_title' );
	$text  = lohkareet_render_translated_content( $work_id );

	if ( ! $names && '' === $title && '' === $type && '' === trim( wp_strip_all_tags( $text ) ) ) {
		return '';
	}

	$html = '<section class="lohkareet-programme__work">';

	if ( $names ) {
		$html .= '<h2>' . implode( '<br>', array_map( 'esc_html', $names ) ) . '</h2>';
	}

	if ( '' !== $title ) {
		$html .= '<p class="lohkareet-programme__title">' . esc_html( $title ) . '</p>';
	}

	if ( '' !== $type ) {
		$html .= '<p class="lohkareet-programme__type">' . esc_html( $type ) . '</p>';
	}

	if ( '' !== trim( wp_strip_all_tags( $text ) ) ) {
		$html .= '<div class="lohkareet-programme__description">' . $text . '</div>';
	}

	$html .= '</section>';

	return $html;
}

/**
 * Place and arrival sections. Empty pieces are omitted.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_context_html( $post_id ) {
	return lohkareet_get_event_place_html( $post_id ) . lohkareet_get_event_arrival_html( $post_id );
}

/**
 * Editorial place text and the event’s Zoneatlas map.
 *
 * An empty Zoneatlas URL keeps the series map as a compact placeholder.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_place_html( $post_id ) {
	$location = lohkareet_get_event_meta_string( $post_id, 'lohkareet_location_description' );

	if ( '' === trim( $location ) ) {
		return '';
	}

	$html  = '<section class="lohkareet-place">';
	$html .= '<h2>' . esc_html__( 'Place', 'lohkareet' ) . '</h2>';
	$html .= '<div class="lohkareet-place__text">' . lohkareet_plain_paragraphs_html( $location ) . '</div>';
	$html .= lohkareet_get_event_place_map_html( $post_id );
	$html .= '</section>';

	return $html;
}

/**
 * Practical arrival text and the external Google Maps link.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_arrival_html( $post_id ) {
	$instructions = lohkareet_get_event_meta_string( $post_id, 'lohkareet_arrival_description' );
	$link         = lohkareet_get_event_maps_link_html( $post_id );

	if ( '' === trim( $instructions ) && '' === $link ) {
		return '';
	}

	$html  = '<section class="lohkareet-arrival">';
	$html .= '<h2>' . esc_html__( 'Arrival', 'lohkareet' ) . '</h2>';

	if ( '' !== trim( $instructions ) ) {
		$html .= '<div class="lohkareet-arrival__text">' . lohkareet_plain_paragraphs_html( $instructions ) . '</div>';
	}

	$html .= $link;
	$html .= '</section>';

	return $html;
}

/**
 * Turns stored plain text into paragraphs.
 *
 * @param string $text Location or arrival text.
 */
function lohkareet_plain_paragraphs_html( $text ) {
	$parts = preg_split( "/\n\s*\n/", trim( $text ) );
	$html  = '';

	if ( ! is_array( $parts ) ) {
		return '';
	}

	foreach ( $parts as $part ) {
		$part = trim( $part );

		if ( '' === $part ) {
			continue;
		}

		$html .= '<p>' . nl2br( esc_html( $part ), false ) . '</p>';
	}

	return $html;
}

/**
 * Verified coordinates and whether they mark the event or the arrival point.
 *
 * @param int $post_id Event ID.
 * @return array{lat: string, lng: string, point: string}|null
 */
function lohkareet_get_event_map_coordinates( $post_id ) {
	$latitude  = lohkareet_sanitize_latitude( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_latitude' ) );
	$longitude = lohkareet_sanitize_longitude( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_longitude' ) );
	$point     = lohkareet_sanitize_map_point( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_point' ) );

	if ( '' === $latitude || '' === $longitude || '' === $point ) {
		return null;
	}

	return array(
		'lat'   => $latitude,
		'lng'   => $longitude,
		'point' => $point,
	);
}

/**
 * Zoneatlas map beside the place text.
 *
 * The event’s own embed replaces the series map when its URL is stored.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_place_map_html( $post_id ) {
	$event_url = lohkareet_with_cooperative_gestures( lohkareet_get_event_meta_string( $post_id, 'lohkareet_zoneatlas_url' ) );
	$url       = '' !== $event_url ? $event_url : lohkareet_get_zoneatlas_map_url();

	if ( '' === $url ) {
		return '';
	}

	$title = lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' );

	if ( '' === $title ) {
		$title = __( 'Lohkareet', 'lohkareet' );
	}

	$iframe = lohkareet_get_map_iframe( $url, $title );

	if ( '' === $iframe ) {
		return '';
	}

	$class = 'lohkareet-place__map';

	if ( '' !== $event_url ) {
		$class .= ' lohkareet-place__map--event';
	}

	return '<div class="' . esc_attr( $class ) . '">' . $iframe . '</div>';
}

/**
 * External Google Maps link. The embedded map does not have to load for this to work.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_maps_link_html( $post_id ) {
	$point = lohkareet_sanitize_map_point( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_point' ) );

	if ( '' === $point ) {
		return '';
	}

	$url = lohkareet_sanitize_https_url( lohkareet_get_event_meta_string( $post_id, 'lohkareet_map_url' ) );

	if ( '' === $url ) {
		$coordinates = lohkareet_get_event_map_coordinates( $post_id );

		if ( null === $coordinates ) {
			return '';
		}

		$url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $coordinates['lat'] . ',' . $coordinates['lng'] );
	}

	if ( 'arrival' === $point ) {
		$label = __( 'Show the arrival point in Google Maps', 'lohkareet' );
	} else {
		$label = __( 'Show in Google Maps', 'lohkareet' );
	}

	return '<p class="lohkareet-arrival__link"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $label ) . '<span class="lohkareet-visually-hidden"> ' . esc_html__( 'Opens in a new tab.', 'lohkareet' ) . '</span></a></p>';
}
