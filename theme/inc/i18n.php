<?php
/**
 * Language integration.
 *
 * Finnish is the primary language and English is the second language.
 * Polylang links translations and selects the WordPress locale. This file
 * does not translate content and does not change the site locale.
 *
 * Polylang’s language slug and the WordPress locale are different values.
 * The predefined Suomi language uses slug `fi` and locale `fi`. English uses
 * slug `en` and locale `en_US`. Theme UI strings follow the locale. The
 * Zoneatlas keys and the event query use the slug.
 *
 * Zoneatlas URLs for each language slug are set in lohkareet_get_zoneatlas_urls().
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zoneatlas embed URL for each language slug.
 *
 * Both addresses are the current embed until the final Finnish and English
 * Zoneatlas URLs are confirmed. The /en/ segment is not treated as the
 * language of the map. Replace the values here, or with the
 * lohkareet_zoneatlas_urls filter. Keep cooperativeGesturesEnabled=true;
 * lohkareet_with_cooperative_gestures() adds it when it is missing.
 *
 * @return array<string, string> Language slug => https URL.
 */
function lohkareet_get_zoneatlas_urls() {
	// Keys are Polylang slugs. Do not key English as en_US; that is the locale.
	$urls = array(
		'fi' => 'https://siirtyma.com/en/lohkareet?cooperativeGesturesEnabled=true',
		'en' => 'https://siirtyma.com/en/lohkareet?cooperativeGesturesEnabled=true',
	);

	/**
	 * Filters the Zoneatlas URL used for each language.
	 *
	 * @param array<string, string> $urls Language slug => URL.
	 */
	$urls = apply_filters( 'lohkareet_zoneatlas_urls', $urls );

	if ( ! is_array( $urls ) ) {
		return array();
	}

	$clean = array();

	foreach ( $urls as $language => $url ) {
		if ( ! is_string( $language ) || ! is_string( $url ) ) {
			continue;
		}

		$clean[ strtolower( $language ) ] = lohkareet_with_cooperative_gestures( $url );
	}

	return $clean;
}

/**
 * Zoneatlas URL for the language currently shown on the front end.
 */
function lohkareet_get_zoneatlas_map_url() {
	$urls     = lohkareet_get_zoneatlas_urls();
	$language = lohkareet_get_current_language();

	if ( isset( $urls[ $language ] ) && '' !== $urls[ $language ] ) {
		return $urls[ $language ];
	}

	if ( isset( $urls['fi'] ) && '' !== $urls['fi'] ) {
		return $urls['fi'];
	}

	foreach ( $urls as $url ) {
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * Adds the Zoneatlas cooperative-gestures flag when a URL does not have it.
 *
 * @param string $url Embed URL.
 */
function lohkareet_with_cooperative_gestures( $url ) {
	$url = lohkareet_sanitize_https_url( $url );

	if ( '' === $url || false !== strpos( $url, 'cooperativeGesturesEnabled=' ) ) {
		return $url;
	}

	$separator = false === strpos( $url, '?' ) ? '?' : '&';

	return $url . $separator . 'cooperativeGesturesEnabled=true';
}

/**
 * Polylang language slug for this request, for the map URL and similar choices.
 *
 * This does not switch the WordPress locale. Without Polylang, the slug is
 * taken from the site locale (`fi` from `fi`, `en` from `en_US`). Finnish is
 * used only when that locale cannot be read. An English WordPress site stays
 * English. Templates should not call this to choose sentences.
 */
function lohkareet_get_current_language() {
	if ( function_exists( 'pll_current_language' ) ) {
		$language = pll_current_language( 'slug' );

		if ( is_string( $language ) && preg_match( '/^[a-z]{2}$/', $language ) ) {
			return $language;
		}
	}

	$locale = get_locale();

	if ( is_string( $locale ) && preg_match( '/^([a-z]{2})/i', $locale, $matches ) ) {
		return strtolower( $matches[1] );
	}

	return 'fi';
}

/**
 * Polylang language slug for an event query, or an empty string when it should not be forced.
 *
 * An empty string leaves filtering to the plugin. Polylang still limits the
 * front end to the current language. Setting lang only when a slug exists
 * avoids asking Polylang for every language by accident.
 */
function lohkareet_get_event_query_language() {
	if ( ! function_exists( 'pll_current_language' ) ) {
		return '';
	}

	$language = pll_current_language( 'slug' );

	if ( ! is_string( $language ) || ! preg_match( '/^[a-z]{2}$/', $language ) ) {
		return '';
	}

	return $language;
}

/**
 * Keeps events, works, contributors, and the navigation available to Polylang.
 *
 * @param mixed $post_types  Post types Polylang can translate.
 * @param bool  $is_settings Unused. Present because Polylang passes it.
 * @return array<string, string>
 */
function lohkareet_register_translatable_event_type( $post_types, $is_settings = false ) {
	unset( $is_settings );

	if ( ! is_array( $post_types ) ) {
		$post_types = array();
	}

	$post_types['event']         = 'event';
	$post_types['contributor']   = 'contributor';
	$post_types['work']          = 'work';
	$post_types['wp_navigation'] = 'wp_navigation';

	return $post_types;
}
add_filter( 'pll_get_post_types', 'lohkareet_register_translatable_event_type', 10, 2 );

/**
 * Keeps the event whose language matches the request.
 *
 * WordPress does not apply a taxonomy query to a singular URL. Finnish and
 * English events share a slug, so the query can return both. The newer post
 * would otherwise become the page, and the canonical redirect would send the
 * Finnish URL to the English one.
 *
 * @param mixed    $posts Posts found for this query.
 * @param WP_Query $query Query being run.
 * @return mixed
 */
function lohkareet_prefer_event_in_current_language( $posts, $query ) {
	if ( ! $query instanceof WP_Query || ! $query->is_main_query() || is_admin() ) {
		return $posts;
	}

	if ( ! is_array( $posts ) || count( $posts ) < 2 || 'event' !== $query->get( 'post_type' ) ) {
		return $posts;
	}

	if ( ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_current_language' ) ) {
		return $posts;
	}

	$language = pll_current_language( 'slug' );

	if ( ! is_string( $language ) || '' === $language ) {
		return $posts;
	}

	$matched = array();

	foreach ( $posts as $post ) {
		if ( $post instanceof WP_Post && pll_get_post_language( $post->ID, 'slug' ) === $language ) {
			$matched[] = $post;
		}
	}

	return $matched ? $matched : $posts;
}
add_filter( 'the_posts', 'lohkareet_prefer_event_in_current_language', 10, 2 );

/**
 * Copies shared event meta to a new translation, and keeps translated text out of later sync.
 *
 * @param mixed $metas Meta keys Polylang is about to copy.
 * @param bool  $sync  True when Polylang is synchronizing an existing translation.
 * @param int   $from  Source post ID. Unused.
 * @param int   $to    Target post ID. Unused.
 * @return string[]
 */
function lohkareet_filter_copied_event_meta( $metas, $sync = false, $from = 0, $to = 0 ) {
	unset( $from, $to );

	if ( ! is_array( $metas ) ) {
		$metas = array();
	}

	$metas = array_merge(
		$metas,
		lohkareet_shared_event_meta_keys(),
		lohkareet_shared_work_meta_keys(),
		array( '_thumbnail_id' )
	);

	if ( $sync ) {
		$metas = array_diff(
			$metas,
			lohkareet_translatable_event_meta_keys(),
			lohkareet_translatable_work_meta_keys(),
			lohkareet_translatable_contributor_meta_keys()
		);
	}

	return array_values( array_unique( array_map( 'strval', $metas ) ) );
}
add_filter( 'pll_copy_post_metas', 'lohkareet_filter_copied_event_meta', 10, 4 );

/**
 * Copies language-independent event meta onto linked translations.
 *
 * Runs only after the event form has been saved, so creating an empty
 * translation does not wipe the source. No-op when Polylang is absent.
 *
 * @param int $post_id Event that was just saved.
 */
function lohkareet_sync_shared_event_meta( $post_id, $keys = null ) {
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		return;
	}

	static $syncing = false;

	if ( $syncing ) {
		return;
	}

	$translations = pll_get_post_translations( $post_id );

	if ( ! is_array( $translations ) || count( $translations ) < 2 ) {
		return;
	}

	$syncing = true;

	foreach ( $translations as $translated_id ) {
		$translated_id = (int) $translated_id;

		if ( $translated_id <= 0 || $translated_id === (int) $post_id ) {
			continue;
		}

		if ( ! current_user_can( 'edit_post', $translated_id ) ) {
			continue;
		}

		$shared_keys = is_array( $keys ) ? $keys : lohkareet_shared_event_meta_keys();

		foreach ( $shared_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );

			if ( ! is_string( $value ) || '' === $value ) {
				delete_post_meta( $translated_id, $key );
				continue;
			}

			update_post_meta( $translated_id, $key, $value );
		}
	}

	$syncing = false;
}

/**
 * Copies the featured image onto linked translations of the same event.
 *
 * Fires when the thumbnail meta changes. The guard stops the write to the
 * other language from copying the image back. No-op without Polylang.
 *
 * @param int $post_id Event whose thumbnail just changed.
 */
function lohkareet_sync_event_featured_image( $post_id ) {
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		return;
	}

	static $syncing = false;

	if ( $syncing ) {
		return;
	}

	$post_id = (int) $post_id;

	if ( $post_id <= 0 || ! in_array( get_post_type( $post_id ), array( 'event', 'contributor' ), true ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$translations = pll_get_post_translations( $post_id );

	if ( ! is_array( $translations ) || count( $translations ) < 2 ) {
		return;
	}

	$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
	$syncing      = true;

	foreach ( $translations as $translated_id ) {
		$translated_id = (int) $translated_id;

		if ( $translated_id <= 0 || $translated_id === $post_id || get_post_type( $translated_id ) !== get_post_type( $post_id ) ) {
			continue;
		}

		if ( ! current_user_can( 'edit_post', $translated_id ) ) {
			continue;
		}

		if ( (int) get_post_thumbnail_id( $translated_id ) === $thumbnail_id ) {
			continue;
		}

		if ( $thumbnail_id > 0 ) {
			set_post_thumbnail( $translated_id, $thumbnail_id );
			continue;
		}

		delete_post_thumbnail( $translated_id );
	}

	$syncing = false;
}

/**
 * Starts a featured-image sync after the thumbnail meta is added or updated.
 *
 * @param int    $meta_id    Meta row ID. Unused.
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value. Unused. The current thumbnail is read instead.
 */
function lohkareet_sync_event_thumbnail_on_change( $meta_id, $post_id, $meta_key, $meta_value ) {
	unset( $meta_id, $meta_value );

	if ( '_thumbnail_id' !== $meta_key ) {
		return;
	}

	lohkareet_sync_event_featured_image( (int) $post_id );
}
add_action( 'added_post_meta', 'lohkareet_sync_event_thumbnail_on_change', 10, 4 );
add_action( 'updated_post_meta', 'lohkareet_sync_event_thumbnail_on_change', 10, 4 );

/**
 * Starts a featured-image sync after the thumbnail meta is removed.
 *
 * @param int[]  $meta_ids   Deleted meta row IDs. Unused.
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Previous value. Unused.
 */
function lohkareet_sync_event_thumbnail_on_delete( $meta_ids, $post_id, $meta_key, $meta_value ) {
	unset( $meta_ids, $meta_value );

	if ( '_thumbnail_id' !== $meta_key ) {
		return;
	}

	lohkareet_sync_event_featured_image( (int) $post_id );
}
add_action( 'deleted_post_meta', 'lohkareet_sync_event_thumbnail_on_delete', 10, 4 );

/**
 * Languages for the header switcher.
 *
 * @return array<int, array{slug: string, locale: string, url: string, current: bool}>
 */
function lohkareet_get_language_switcher_items() {
	$items = array();

	if ( ! function_exists( 'pll_the_languages' ) ) {
		return array();
	}

	$languages = pll_the_languages(
		array(
			'raw'                    => 1,
			'echo'                   => 0,
			'hide_if_empty'          => 0,
			'hide_if_no_translation' => 0,
		)
	);

	if ( ! is_array( $languages ) ) {
		return array();
	}

	foreach ( $languages as $language ) {
		if ( ! is_array( $language ) || empty( $language['slug'] ) || empty( $language['url'] ) ) {
			continue;
		}

		$slug   = sanitize_key( (string) $language['slug'] );
		$locale = isset( $language['locale'] ) ? (string) $language['locale'] : $slug;

		if ( '' === $slug ) {
			continue;
		}

		$items[] = array(
			'slug'    => $slug,
			'locale'  => $locale,
			'url'     => (string) $language['url'],
			'current' => ! empty( $language['current_lang'] ),
		);
	}

	usort(
		$items,
		static function ( $a, $b ) {
			$order = array(
				'fi' => 0,
				'en' => 1,
			);
			$a_order = isset( $order[ $a['slug'] ] ) ? $order[ $a['slug'] ] : 2;
			$b_order = isset( $order[ $b['slug'] ] ) ? $order[ $b['slug'] ] : 2;

			if ( $a_order === $b_order ) {
				return strcmp( $a['slug'], $b['slug'] );
			}

			return $a_order <=> $b_order;
		}
	);

	return $items;
}

/**
 * Header language switcher, or an empty string when a plugin has not provided languages.
 */
function lohkareet_get_language_switcher_markup() {
	$items = lohkareet_get_language_switcher_items();

	if ( count( $items ) < 2 ) {
		return '';
	}

	$current = $items[0];

	foreach ( $items as $item ) {
		if ( $item['current'] ) {
			$current = $item;
			break;
		}
	}

	$html  = '<nav ' . get_block_wrapper_attributes(
		array(
			'class'      => 'lohkareet-language-switcher',
			'aria-label' => __( 'Language', 'lohkareet' ),
		)
	) . '>';
	$html .= '<button type="button" class="lohkareet-language-switcher__trigger" aria-expanded="false" aria-controls="lohkareet-languages" aria-haspopup="true">';
	$html .= '<span class="lohkareet-language-switcher__label">' . esc_html( strtoupper( $current['slug'] ) ) . '</span>';
	$html .= '<span class="lohkareet-language-switcher__mark" aria-hidden="true"></span>';
	$html .= '</button>';
	$html .= '<ul id="lohkareet-languages">';

	foreach ( $items as $item ) {
		$label        = strtoupper( $item['slug'] );
		$hreflang     = str_replace( '_', '-', $item['locale'] );
		$current_item = $item['slug'] === $current['slug'];

		if ( $current_item ) {
			$html .= '<li class="is-current"><span aria-current="page" lang="' . esc_attr( $item['slug'] ) . '">' . esc_html( $label ) . '</span></li>';
			continue;
		}

		$html .= '<li><a href="' . esc_url( $item['url'] ) . '" hreflang="' . esc_attr( $hreflang ) . '" lang="' . esc_attr( $item['slug'] ) . '">' . esc_html( $label ) . '</a></li>';
	}

	$html .= '</ul></nav>';

	return $html;
}

/**
 * Renders the language switcher. Without a multilingual plugin this is empty on the public site.
 *
 * @param array $attributes Block attributes.
 */
function lohkareet_render_language_switcher( $attributes = array(), $content = '', $block = null ) {
	unset( $attributes, $content, $block );

	$html = lohkareet_get_language_switcher_markup();

	if ( '' !== $html ) {
		return $html;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return '<nav ' . get_block_wrapper_attributes(
			array(
				'class'       => 'lohkareet-language-switcher',
				'aria-hidden' => 'true',
			)
		) . '><ul><li>FI</li><li>EN</li></ul></nav>';
	}

	return '';
}

/**
 * Gives the editor script the same Finnish translations as PHP.
 */
function lohkareet_inject_editor_translations() {
	$msgids = array(
		'Lohkareet map',
		'Map URL',
		'Leave empty to use the map for the current language.',
		'Map name',
		'Name announced to screen readers.',
		'Next event',
		'Event list',
		'Event details',
		'Language switcher',
		'Hero',
		'Menu toggle',
		'Menu',
		'Close',
		'Home',
		'Page hero',
		'Site mark',
		'Footer',
		'Search results for “%s”',
	);
	$data   = array(
		'' => array(
			'domain' => 'lohkareet',
			'lang'   => get_locale(),
		),
	);

	foreach ( $msgids as $msgid ) {
		$translated = __( $msgid, 'lohkareet' );

		if ( $translated !== $msgid ) {
			$data[ $msgid ] = array( $translated );
		}
	}

	if ( count( $data ) < 2 ) {
		return;
	}

	wp_add_inline_script(
		'lohkareet-blocks-editor',
		'wp.i18n.setLocaleData( ' . wp_json_encode( $data ) . ', "lohkareet" );',
		'before'
	);
}
