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
				'title'  => array(
					'type'    => 'string',
					'default' => 'Lohkareet',
				),
				'layout' => array(
					'type'    => 'string',
					'default' => '',
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
 * Hero title HTML, with a syllable break for the two words that do not fit a phone.
 *
 * The marker is hidden from the width where the word fits on one line, so the
 * stored title, the menu, and other heroes stay untouched.
 *
 * @param string $title Page title.
 * @return string
 */
function lohkareet_get_page_hero_title_html( $title ) {
	$breaks = array(
		'varustautuminen' => array( 'Varustau', 'tuminen' ),
		'preparation'     => array( 'Prepara', 'tion' ),
	);
	$key    = strtolower( $title );

	if ( ! isset( $breaks[ $key ] ) ) {
		return esc_html( $title );
	}

	return esc_html( $breaks[ $key ][0] )
		. '<span class="lohkareet-page-hero__break" aria-hidden="true"></span><span class="lohkareet-page-hero__rest">'
		. esc_html( $breaks[ $key ][1] )
		. '</span>';
}

/**
 * Puts a real space between the English homepage title's two words.
 *
 * The Finnish title splits one compound, siirtolohkareille, across two spans.
 * English reuses those spans for "for" and "glacial erratics".
 *
 * @param string $content Post content.
 */
function lohkareet_space_english_intro_heading( $content ) {
	if ( ! is_string( $content ) || ! is_front_page() || 'en' !== lohkareet_get_current_language() ) {
		return $content;
	}

	$spaced = preg_replace(
		'/(<span class="lohkareet-intro-heading__word">[^<]*<\/span>)(<span class="lohkareet-intro-heading__word">)/',
		'$1 $2',
		$content,
		1
	);

	return is_string( $spaced ) ? $spaced : $content;
}
add_filter( 'the_content', 'lohkareet_space_english_intro_heading', 8 );

/**
 * Whether this page is Varustautuminen in either language.
 *
 * @param WP_Post $post Page being rendered.
 */
function lohkareet_is_preparation_post( $post ) {
	$slugs = array( $post->post_name );

	if ( function_exists( 'pll_get_post' ) ) {
		foreach ( array( 'fi', 'en' ) as $language ) {
			$translated = pll_get_post( $post->ID, $language );

			if ( $translated ) {
				$slugs[] = (string) get_post_field( 'post_name', $translated );
			}
		}
	}

	return in_array( 'varustautuminen', $slugs, true ) || in_array( 'preparation', $slugs, true );
}

/**
 * Icons and short additions for one preparation paragraph.
 *
 * The stored paragraph is left as it is. A paragraph earns an icon only when
 * its own words already carry that subject. The shoe sentence is not added:
 * the paragraph already asks for sturdy waterproof shoes.
 *
 * @param string $paragraph Rendered paragraph HTML.
 * @return array{icons: string[], notes: string[]}|null
 */
function lohkareet_prepare_paragraph_plan( $paragraph ) {
	$text = html_entity_decode( wp_strip_all_tags( $paragraph ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );

	if ( ! is_string( $text ) || '' === $text ) {
		return null;
	}

	$coat  = str_starts_with( $text, 'Syksyinen ja talvinen metsä' ) || str_starts_with( $text, 'Pukeudu lämpimästi' ) || str_starts_with( $text, 'Dress warmly' );
	$lamp  = str_starts_with( $text, 'Otsa- tai taskulamppu' );
	$icons = array();
	$notes = array();

	if ( $coat ) {
		$icons[] = 'takki';
	}

	if ( $coat && ( str_contains( $text, 'jalkine' ) || str_contains( $text, 'shoe' ) ) ) {
		$icons[] = 'kenka';
	}

	if ( $lamp || ( $coat && ( str_contains( $text, 'lamp' ) || str_contains( $text, 'torch' ) ) ) ) {
		$icons[] = 'lamppu';
		$notes[] = 'charge';
		$notes[] = 'glare';
	}

	if ( ! $icons ) {
		return null;
	}

	return array(
		'icons' => $icons,
		'notes' => $notes,
	);
}

/**
 * One preparation drawing. Empty alt: the neighbouring paragraph says the same thing.
 *
 * @param string $icon Icon key.
 */
function lohkareet_prepare_icon_html( $icon ) {
	$files = array(
		'takki'   => 'ikoni-takki.png',
		'kenka'   => 'ikoni-kenka.png',
		'lamppu'  => 'ikoni-lamppu.png',
		'haikase' => 'ikoni-haikase.png',
		'auto'    => 'ikoni-auto.png',
	);

	if ( ! isset( $files[ $icon ] ) ) {
		return '';
	}

	$url = get_theme_file_uri( 'assets/images/' . $files[ $icon ] );

	return '<img class="lohkareet-prepare__icon lohkareet-prepare__icon--' . esc_attr( $icon ) . '" src="' . esc_url( $url ) . '" alt="" width="1024" height="1024" decoding="async" />';
}

/**
 * Visible text of one rendered paragraph.
 *
 * @param string $paragraph Paragraph HTML.
 */
function lohkareet_prepare_plain_text( $paragraph ) {
	$text = html_entity_decode( wp_strip_all_tags( $paragraph ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );

	return is_string( $text ) ? $text : '';
}

/**
 * Which stored addition a paragraph is, when the editor holds it.
 *
 * @param string $paragraph Paragraph HTML.
 */
function lohkareet_prepare_stored_note_key( $paragraph ) {
	$text = lohkareet_prepare_plain_text( $paragraph );

	if ( '' === $text ) {
		return '';
	}

	if ( str_starts_with( $text, 'Turn off your headlamp' ) ) {
		return 'headlamp';
	}

	if ( str_contains( $text, 'paluumatkalle' ) || str_contains( $text, 'way back' ) ) {
		return 'charge';
	}

	if ( str_contains( $text, 'suoraan kasvoihin' ) || str_contains( $text, 'other visitors' ) ) {
		return 'glare';
	}

	if ( str_contains( $text, 'autolla perille' ) || str_contains( $text, 'reached by car' ) ) {
		return 'arrival';
	}

	return '';
}

/**
 * Whether this addition belongs with the paragraph's icons.
 *
 * @param string                              $key  Note key.
 * @param array{icons: string[], notes: string[]} $plan Icon plan.
 */
function lohkareet_prepare_note_belongs( $key, $plan ) {
	if ( in_array( $key, array( 'headlamp', 'charge', 'glare' ), true ) ) {
		return in_array( 'charge', $plan['notes'], true );
	}

	return false;
}

/**
 * A short addition. English is the source; Finnish lives in the translation file.
 *
 * Used only when the editor does not already contain the sentence.
 *
 * @param string $note charge, glare, or headlamp.
 */
function lohkareet_prepare_note_html( $note ) {
	if ( 'charge' === $note ) {
		$text = __( 'Make sure the light has enough power for the way back as well.', 'lohkareet' );
	} elseif ( 'glare' === $note ) {
		$text = __( 'Avoid shining the light straight into other visitors’ faces. Point it at the ground when you need to, and let your eyes adjust to the dark.', 'lohkareet' );
	} elseif ( 'headlamp' === $note ) {
		$text = 'Turn off your headlamp when you arrive at the boulder, when it is safe to do so.';
	} else {
		return '';
	}

	return '<p class="wp-block-paragraph">' . esc_html( $text ) . '</p>';
}

/**
 * The sentence to show: the editor's own paragraph, or the shared line if it is missing.
 *
 * @param string                              $key       Note key.
 * @param string                              $paragraph Anchor paragraph.
 * @param array<string, string>               $found     Editor paragraphs already matched.
 * @param array{icons: string[], notes: string[]} $plan  Icon plan.
 * @param array<string, bool>                 $present   Notes already stored somewhere on the page.
 */
function lohkareet_prepare_note_piece( $key, $paragraph, $found, $plan, $present = array() ) {
	if ( isset( $found[ $key ] ) ) {
		return $found[ $key ];
	}

	if ( ! empty( $present[ $key ] ) || ! lohkareet_prepare_note_belongs( $key, $plan ) ) {
		return '';
	}

	$stored = lohkareet_prepare_plain_text( $paragraph );

	if ( 'headlamp' === $key ) {
		if ( 'en' !== lohkareet_get_current_language() || str_contains( $stored, 'sammuttaa otsalampun' ) || str_contains( $stored, 'Turn off your headlamp' ) ) {
			return '';
		}
	} elseif ( '' !== lohkareet_prepare_stored_note_key( $paragraph ) && lohkareet_prepare_stored_note_key( $paragraph ) === $key ) {
		return '';
	}

	return lohkareet_prepare_note_html( $key );
}

/**
 * Wraps one stored paragraph with its drawings. The paragraph HTML is not rewritten.
 *
 * @param string                              $paragraph Anchor paragraph.
 * @param array{icons: string[], notes: string[]} $plan  Icons and additions.
 * @param array<string, string>               $found     Editor paragraphs for the additions.
 * @param array<string, bool>                 $present   Notes already stored somewhere on the page.
 */
function lohkareet_prepare_group_html( $paragraph, $plan, $found = array(), $present = array() ) {
	$clothing = array();
	$lamp     = false;

	foreach ( $plan['icons'] as $icon ) {
		if ( 'lamppu' === $icon ) {
			$lamp = true;
			continue;
		}

		$clothing[] = $icon;
	}

	$headlamp = lohkareet_prepare_note_piece( 'headlamp', $paragraph, $found, $plan, $present );
	$charge   = lohkareet_prepare_note_piece( 'charge', $paragraph, $found, $plan, $present );
	$light    = $headlamp . $charge;
	$split    = $lamp && $clothing && '' !== $light;
	$html     = '<div class="lohkareet-prepare">';

	if ( $split ) {
		$marks = '';

		foreach ( $clothing as $icon ) {
			$marks .= lohkareet_prepare_icon_html( $icon );
		}

		$html .= '<div class="lohkareet-prepare__marks">' . $marks . '</div>';
		$html .= '<div class="lohkareet-prepare__text">' . $paragraph . '</div>';
		$html .= '<div class="lohkareet-prepare__marks">' . lohkareet_prepare_icon_html( 'lamppu' ) . '</div>';
		$html .= '<div class="lohkareet-prepare__text">' . $light . '</div>';
	} else {
		$marks = '';

		foreach ( $plan['icons'] as $icon ) {
			$marks .= lohkareet_prepare_icon_html( $icon );
		}

		$html .= '<div class="lohkareet-prepare__marks">' . $marks . '</div>';
		$html .= '<div class="lohkareet-prepare__text">' . $paragraph . $light . '</div>';
	}

	$glare = lohkareet_prepare_note_piece( 'glare', $paragraph, $found, $plan, $present );

	if ( '' !== $glare ) {
		$html .= '<div class="lohkareet-prepare__marks">' . lohkareet_prepare_icon_html( 'haikase' ) . '</div>';
		$html .= '<div class="lohkareet-prepare__text">' . $glare . '</div>';
	}

	return $html . '</div>';
}

/**
 * The car icon belongs with shared rides, not with parking or driving directions.
 *
 * @return array{heading: string, paragraphs: string[]}
 */
function lohkareet_carpool_copy() {
	return array(
		'heading'    => __( 'Share the journey', 'lohkareet' ),
		'paragraphs' => array(
			__( 'Whenever possible, share a ride and travel together to reduce the environmental impact of getting there.', 'lohkareet' ),
			__( 'Every location requires a walk for the final part of the journey. Check the event-specific directions and allow enough time to reach the site on foot.', 'lohkareet' ),
		),
	);
}

/**
 * A phrase that identifies one carpool paragraph in either language.
 *
 * @param string $text Plain text.
 */
function lohkareet_carpool_body_marker( $text ) {
	if ( str_contains( $text, 'share a ride' ) ) {
		return 'share a ride';
	}

	if ( str_contains( $text, 'kimppakyytejä ja saavu' ) ) {
		return 'kimppakyytejä ja saavu';
	}

	if ( str_contains( $text, 'final part of the journey' ) ) {
		return 'final part of the journey';
	}

	if ( str_contains( $text, 'viimeinen osuus' ) ) {
		return 'viimeinen osuus';
	}

	return '';
}

/**
 * Whether this paragraph is the carpool heading.
 *
 * @param string $text Plain text.
 */
function lohkareet_prepare_is_carpool_heading( $text ) {
	return str_starts_with( $text, 'Suosi kimppakyytejä' ) || str_starts_with( $text, 'Share the journey' );
}

/**
 * Heading and paragraphs for the carpool icon. Editor paragraphs replace the shared lines.
 *
 * @param string   $heading    Heading text. Empty uses the shared heading.
 * @param string[] $paragraphs Editor paragraph HTML already found.
 */
function lohkareet_carpool_text_html( $heading = '', $paragraphs = array() ) {
	$copy = lohkareet_carpool_copy();

	if ( '' === trim( $heading ) ) {
		$heading = $copy['heading'];
	}

	$html  = '<h2>' . esc_html( $heading ) . '</h2>';
	$found = '';

	foreach ( $paragraphs as $paragraph ) {
		$plain = lohkareet_prepare_plain_text( $paragraph );

		if ( '' === $plain || '' === lohkareet_carpool_body_marker( $plain ) ) {
			continue;
		}

		$found .= ' ' . $plain;
		$html  .= str_starts_with( $paragraph, '<p' ) ? $paragraph : '<p>' . esc_html( $plain ) . '</p>';
	}

	foreach ( $copy['paragraphs'] as $paragraph ) {
		$marker = lohkareet_carpool_body_marker( $paragraph );

		if ( '' === $marker || str_contains( $found, $marker ) ) {
			continue;
		}

		$html .= '<p>' . esc_html( $paragraph ) . '</p>';
	}

	return $html;
}

/**
 * Car icon beside the carpool text. Same drawing and scale as the other preparation icons.
 *
 * @param string $text_html Heading and paragraphs.
 */
function lohkareet_get_carpool_group_html( $text_html ) {
	return '<div class="lohkareet-prepare"><div class="lohkareet-prepare__marks">' . lohkareet_prepare_icon_html( 'auto' ) . '</div><div class="lohkareet-prepare__text">' . $text_html . '</div></div>';
}

/**
 * The same short carpool note on every event page, apart from the arrival instructions.
 */
function lohkareet_get_carpool_section_html() {
	return '<section class="lohkareet-share">' . lohkareet_get_carpool_group_html( lohkareet_carpool_text_html() ) . '</section>';
}

/**
 * Places the preparation drawings beside the paragraphs that already say those things.
 *
 * Stored sentences stay in the editor. This only wraps them on the way out.
 *
 * @param string $content Rendered page content.
 */
function lohkareet_decorate_preparation_content( $content ) {
	if ( ! is_string( $content ) || is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type || ! lohkareet_is_preparation_post( $post ) ) {
		return $content;
	}

	if ( str_contains( $content, 'lohkareet-prepare' ) ) {
		return $content;
	}

	if ( ! preg_match_all( '/<(p|h2)\b[^>]*>.*?<\/\1>/s', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$paragraphs  = $matches[0];
	$skip        = array();
	$html        = '';
	$cursor      = 0;
	$saw_carpool = false;
	$present     = array(
		'headlamp' => str_contains( $content, 'Turn off your headlamp' ) || str_contains( $content, 'sammuttaa otsalampun' ),
		'charge'   => str_contains( $content, 'paluumatkalle' ) || str_contains( $content, 'way back' ),
		'glare'    => str_contains( $content, 'suoraan kasvoihin' ) || str_contains( $content, 'other visitors' ),
	);

	foreach ( $paragraphs as $index => $paragraph ) {
		if ( isset( $skip[ $index ] ) ) {
			continue;
		}

		$markup = $paragraph[0];
		$start  = $paragraph[1];
		$html  .= substr( $content, $cursor, $start - $cursor );
		$plain  = lohkareet_prepare_plain_text( $markup );

		if ( 'arrival' === lohkareet_prepare_stored_note_key( $markup ) ) {
			$cursor = $start + strlen( $markup );
			continue;
		}

		if ( lohkareet_prepare_is_carpool_heading( $plain ) ) {
			$bodies   = array();
			$cursor   = $start + strlen( $markup );
			$previous = $cursor;

			for ( $next = $index + 1; $next < count( $paragraphs ); $next++ ) {
				$between = substr( $content, $previous, $paragraphs[ $next ][1] - $previous );

				if ( '' !== trim( wp_strip_all_tags( $between ) ) ) {
					break;
				}

				$body = lohkareet_prepare_plain_text( $paragraphs[ $next ][0] );

				if ( '' === lohkareet_carpool_body_marker( $body ) ) {
					break;
				}

				$bodies[]      = $paragraphs[ $next ][0];
				$skip[ $next ] = true;
				$previous      = $paragraphs[ $next ][1] + strlen( $paragraphs[ $next ][0] );
				$cursor        = $previous;
			}

			$html       .= lohkareet_get_carpool_group_html( lohkareet_carpool_text_html( $plain, $bodies ) );
			$saw_carpool = true;
			continue;
		}

		$plan = lohkareet_prepare_paragraph_plan( $markup );

		if ( null === $plan ) {
			$html  .= $markup;
			$cursor = $start + strlen( $markup );
			continue;
		}

		$found    = array();
		$cursor   = $start + strlen( $markup );
		$previous = $cursor;

		for ( $next = $index + 1; $next < count( $paragraphs ); $next++ ) {
			$between = substr( $content, $previous, $paragraphs[ $next ][1] - $previous );

			if ( '' !== trim( wp_strip_all_tags( $between ) ) ) {
				break;
			}

			$key = lohkareet_prepare_stored_note_key( $paragraphs[ $next ][0] );

			if ( '' === $key || isset( $found[ $key ] ) || ! lohkareet_prepare_note_belongs( $key, $plan ) ) {
				break;
			}

			$found[ $key ] = $paragraphs[ $next ][0];
			$skip[ $next ] = true;
			$previous      = $paragraphs[ $next ][1] + strlen( $paragraphs[ $next ][0] );
			$cursor        = $previous;
		}

		$html .= lohkareet_prepare_group_html( $markup, $plan, $found, $present );
	}

	$html .= substr( $content, $cursor );

	if ( $saw_carpool ) {
		return $html;
	}

	$block    = lohkareet_get_carpool_group_html( lohkareet_carpool_text_html() );
	$closings = array(
		'Anna aikaa saapumiselle.',
		'Allow time for arrival.',
	);
	$inserted = false;

	foreach ( $closings as $closing ) {
		$pos = strpos( $html, $closing );

		if ( false === $pos ) {
			continue;
		}

		$start = strrpos( substr( $html, 0, $pos ), '<p' );

		if ( false === $start ) {
			continue;
		}

		$html     = substr( $html, 0, $start ) . $block . substr( $html, $start );
		$inserted = true;
		break;
	}

	if ( $inserted ) {
		return $html;
	}

	$anchors = array(
		'Tarkemmat saapumisohjeet julkaistaan tapahtumakohtaisesti.',
		'More specific arrival and safety instructions are published for each event.',
	);

	foreach ( $anchors as $anchor ) {
		$pos = strpos( $html, $anchor );

		if ( false === $pos ) {
			continue;
		}

		$end = strpos( $html, '</p>', $pos );

		if ( false === $end ) {
			continue;
		}

		$end     += 4;
		$html     = substr( $html, 0, $end ) . $block . substr( $html, $end );
		$inserted = true;
		break;
	}

	return $inserted ? $html : $html . $block;
}
add_filter( 'the_content', 'lohkareet_decorate_preparation_content', 12 );

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

	$image = '';

	if ( is_singular( 'event' ) ) {
		$post_id = get_queried_object_id();
		$title   = get_the_title( $post_id );
		$before  = lohkareet_get_event_hero_when_html( $post_id );
		$extra   = lohkareet_get_event_hero_place_html( $post_id );
		$image   = lohkareet_get_event_hero_image_html( $post_id );
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

	$classes = 'lohkareet-page-hero';

	if ( '' !== $image ) {
		$classes .= ' has-image';
	}

	$html  = '<section ' . get_block_wrapper_attributes( array( 'class' => $classes ) ) . '>';
	$html .= $image;
	$html .= '<div class="lohkareet-page-hero__grid editorial-container">';
	$html .= $before;
	$title_html = lohkareet_get_page_hero_title_html( $title );
	$label      = false !== strpos( $title_html, 'lohkareet-page-hero__break' )
		? ' aria-label="' . esc_attr( $title ) . '"'
		: '';

	$html .= '<h1 class="lohkareet-page-hero__title"' . $label . '>' . $title_html . '</h1>';
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

	if ( isset( $attributes['layout'] ) && 'page' === $attributes['layout'] ) {
		return lohkareet_get_fullscreen_map_html();
	}

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

	$html = '<div ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-map' ) ) . '>' . $iframe . '</div>';

	if ( is_front_page() ) {
		$html .= lohkareet_get_map_page_button_html();
	}

	return $html;
}

/**
 * Whether the current request is the full-page map.
 */
function lohkareet_is_map_page() {
	if ( ! is_page() ) {
		return false;
	}

	$post = get_queried_object();

	return $post instanceof WP_Post && in_array( $post->post_name, array( 'kartta', 'map' ), true );
}

/**
 * Published map page in the language of this request.
 *
 * The Finnish page is the lookup key. Polylang returns its English pair.
 */
function lohkareet_get_map_page_url() {
	if ( ! function_exists( 'lohkareet_get_published_page_by_slug' ) || ! function_exists( 'lohkareet_navigation_page_for_current_language' ) ) {
		return '';
	}

	$page = lohkareet_get_published_page_by_slug( 'kartta' );

	if ( ! $page instanceof WP_Post ) {
		return '';
	}

	$page_id = lohkareet_navigation_page_for_current_language( $page->ID );
	$url     = get_permalink( $page_id );

	return is_string( $url ) ? $url : '';
}

/**
 * Link from the front-page embed to the map page. Empty until that page exists.
 */
function lohkareet_get_map_page_button_html() {
	$url = lohkareet_get_map_page_url();

	if ( '' === $url ) {
		return '';
	}

	return '<p class="lohkareet-map-open lohkareet-arrival__link"><a href="' . esc_url( $url ) . '">' . esc_html__( 'Explore the map', 'lohkareet' ) . '</a></p>';
}

/**
 * Document title for the map page. A Rank Math title stored on the page wins.
 *
 * @param string $title Title already chosen by WordPress or Rank Math.
 */
function lohkareet_map_document_title( $title ) {
	if ( ! lohkareet_is_map_page() ) {
		return $title;
	}

	$custom = get_post_meta( get_queried_object_id(), 'rank_math_title', true );

	if ( is_string( $custom ) && '' !== trim( $custom ) ) {
		return $title;
	}

	return __( 'Lohkareet – Map', 'lohkareet' );
}
add_filter( 'pre_get_document_title', 'lohkareet_map_document_title', 20 );
add_filter( 'rank_math/frontend/title', 'lohkareet_map_document_title', 20 );

/**
 * Full-window map. The embed URL is the same source as the front page.
 */
function lohkareet_get_fullscreen_map_html() {
	$url = lohkareet_get_zoneatlas_map_url();

	if ( '' === $url ) {
		return '';
	}

	$page    = get_queried_object();
	$heading = $page instanceof WP_Post ? get_the_title( $page ) : '';

	if ( '' === trim( $heading ) ) {
		$heading = __( 'Map', 'lohkareet' );
	}

	$iframe = sprintf(
		'<iframe class="lohkareet-map__frame" title="%1$s" src="%2$s" allow="fullscreen; geolocation" allowfullscreen></iframe>',
		esc_attr__( 'Lohkareet – interactive map', 'lohkareet' ),
		esc_url( $url )
	);

	$fallback = sprintf(
		'<p class="lohkareet-map-stage__fallback lohkareet-arrival__link" data-lohkareet-map-fallback hidden><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="lohkareet-visually-hidden"> %3$s</span></a></p>',
		esc_url( $url ),
		esc_html__( 'Open the map', 'lohkareet' ),
		esc_html__( 'Opens in a new tab.', 'lohkareet' )
	);

	return '<main id="wp--skip-link--target" class="lohkareet-map-page"><h1 class="lohkareet-visually-hidden">' . esc_html( $heading ) . '</h1><div class="lohkareet-map-stage"><p class="lohkareet-map-stage__status" data-lohkareet-map-status>' . esc_html__( 'The map is loading.', 'lohkareet' ) . '</p>' . $iframe . $fallback . '</div></main>';
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
	$when_html    = lohkareet_get_event_card_when_html( $post_id );
	$place        = lohkareet_get_event_listing_place( $post_id );
	$artist = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_name' );
	$status = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_status' );

	$html  = '<a class="lohkareet-event-summary" href="' . esc_url( $permalink ) . '">';
	$html .= '<span class="lohkareet-event-summary__body">';

	if ( '' !== $when_html ) {
		$html .= '<p class="lohkareet-event-summary__when">' . $when_html . '</p>';
	}

	$html .= '<h' . $heading_level . ' class="lohkareet-event-summary__name">' . esc_html( $name ) . '</h' . $heading_level . '>';

	if ( '' !== $place ) {
		$html .= '<p class="lohkareet-event-summary__place">' . esc_html( $place ) . '</p>';
	} else {
		$html .= '<p class="lohkareet-event-summary__place">' . esc_html__( 'The venue will be confirmed.', 'lohkareet' ) . '</p>';
	}

	$html .= '<p class="lohkareet-event-summary__artist">' . lohkareet_get_event_card_artist_html( $artist, $status ) . '</p>';

	$html .= '</span>';
	$html .= '<span class="lohkareet-event-summary__arrow" aria-hidden="true">';
	$html .= '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" focusable="false" xmlns="http://www.w3.org/2000/svg">';
	$html .= '<path d="M25.5625 4.4375C22.6458 1.47917 19.125 0 15 0C10.875 0 7.34375 1.46875 4.40625 4.40625C1.46875 7.34375 0 10.875 0 15C0 19.125 1.46875 22.6562 4.40625 25.5938C7.34375 28.5312 10.875 30 15 30C19.125 30 22.6562 28.5312 25.5938 25.5938C28.5312 22.6562 30 19.125 30 15C30 10.875 28.5208 7.35417 25.5625 4.4375ZM23.9375 14.625C23.9792 14.75 24 14.875 24 15C24 15.125 23.9792 15.25 23.9375 15.375C23.8958 15.5 23.8125 15.6042 23.6875 15.6875L17.6875 21.6875C17.2292 22.1875 16.7604 22.1979 16.2812 21.7188C15.8021 21.2396 15.8125 20.7708 16.3125 20.3125L20.5625 16H5C4.70833 16 4.46875 15.9062 4.28125 15.7188C4.09375 15.5312 4 15.2917 4 15C4 14.7083 4.09375 14.4688 4.28125 14.2812C4.46875 14.0938 4.70833 14 5 14H20.5625L16.3125 9.6875C15.8125 9.22917 15.8021 8.76042 16.2812 8.28125C16.7604 7.80208 17.2292 7.8125 17.6875 8.3125L23.6875 14.3125C23.8125 14.3958 23.8958 14.5 23.9375 14.625Z" fill="currentColor"/>';
	$html .= '</svg></span>';
	$html .= '</a>';

	return $html;
}

/**
 * Date line for one event card.
 *
 * A complete date stays as the compact listing line. A month or a year
 * without a day is shown as stored, with the time called out when no clock
 * exists. An event with no date says so instead of leaving the row blank.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_card_when_html( $post_id ) {
	$when = lohkareet_get_event_listing_when_html( $post_id );

	if ( '' === $when ) {
		$when = lohkareet_get_event_date_html( $post_id );
	}

	if ( '' === $when ) {
		return esc_html__( 'The date will be confirmed.', 'lohkareet' );
	}

	if ( '' === lohkareet_get_event_listing_time_html( $post_id ) ) {
		$when .= ' · ' . esc_html__( 'The time will be confirmed.', 'lohkareet' );
	}

	return $when;
}

/**
 * Artist line for one event card. Uses the same words as the event page.
 *
 * @param string $artist Artist name.
 * @param string $status confirmed, tentative, or unannounced.
 */
function lohkareet_get_event_card_artist_html( $artist, $status ) {
	if ( 'confirmed' === $status && '' !== $artist ) {
		return esc_html( $artist );
	}

	if ( 'tentative' === $status && '' !== $artist ) {
		return esc_html(
			sprintf(
				/* translators: %s: artist name that is not yet confirmed. */
				__( 'Tentative: %s', 'lohkareet' ),
				$artist
			)
		);
	}

	return esc_html__( 'The artist will be announced later.', 'lohkareet' );
}

/**
 * Link to the preparation page in the language of this request.
 *
 * The label is the published page title. An unpublished page adds nothing.
 */
function lohkareet_get_preparation_link_html() {
	if ( ! function_exists( 'lohkareet_get_published_page_by_slug' ) || ! function_exists( 'lohkareet_navigation_page_for_current_language' ) ) {
		return '';
	}

	$page = lohkareet_get_published_page_by_slug( 'varustautuminen' );

	if ( ! $page instanceof WP_Post ) {
		return '';
	}

	$page_id = lohkareet_navigation_page_for_current_language( $page->ID );
	$url     = get_permalink( $page_id );
	$label   = get_the_title( $page_id );

	if ( ! is_string( $url ) || '' === $url || ! is_string( $label ) || '' === trim( $label ) ) {
		return '';
	}

	return '<section class="lohkareet-event-prepare"><p class="lohkareet-arrival__link"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p></section>';
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
 * Event image for the hero. Empty when the event has no featured image.
 *
 * The same image is not printed on the front page. Listings stay text.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_get_event_hero_image_html( $post_id ) {
	$thumbnail_id = (int) get_post_thumbnail_id( $post_id );

	if ( $thumbnail_id <= 0 || ! wp_attachment_is_image( $thumbnail_id ) ) {
		return '';
	}

	return wp_get_attachment_image(
		$thumbnail_id,
		'full',
		false,
		array(
			'class'         => 'lohkareet-page-hero__image',
			'sizes'         => '100vw',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
		)
	);
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
	return lohkareet_get_event_place_html( $post_id ) . lohkareet_get_event_arrival_html( $post_id ) . lohkareet_get_preparation_link_html() . lohkareet_get_carpool_section_html();
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
