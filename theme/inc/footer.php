<?php
/**
 * Global footer.
 *
 * One template part renders this on every public template. Navigation URLs
 * come from the published Navigation menu plus the shared front-page helper.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the footer block and its stylesheet.
 */
function lohkareet_register_footer() {
	register_block_type(
		'lohkareet/site-footer',
		array(
			'api_version'     => 3,
			'title'           => __( 'Footer', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_site_footer',
		)
	);
}
add_action( 'init', 'lohkareet_register_footer' );

/**
 * Loads the footer stylesheet on the public site and in the editor canvas.
 */
function lohkareet_enqueue_footer() {
	wp_enqueue_style(
		'lohkareet-footer',
		get_theme_file_uri( 'assets/css/footer.css' ),
		array( 'lohkareet-layout' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'lohkareet_enqueue_footer', 20 );

/**
 * Loads footer CSS in the block editor canvas.
 */
function lohkareet_footer_editor_style() {
	add_editor_style( 'assets/css/footer.css' );
}
add_action( 'after_setup_theme', 'lohkareet_footer_editor_style' );

/**
 * Published Navigation post, in the current language when Polylang provides one.
 *
 * @return WP_Post|null
 */
function lohkareet_get_footer_navigation_post() {
	$posts = get_posts(
		array(
			'post_type'        => 'wp_navigation',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'meta_key'         => '_lohkareet_primary_navigation',
			'meta_value'       => '1',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	if ( ! $posts ) {
		$posts = get_posts(
			array(
				'post_type'        => 'wp_navigation',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
	}

	if ( ! $posts || ! isset( $posts[0] ) || ! $posts[0] instanceof WP_Post ) {
		return null;
	}

	$post = $posts[0];

	if ( ! function_exists( 'pll_get_post' ) || ! function_exists( 'pll_current_language' ) ) {
		return $post;
	}

	$language = pll_current_language( 'slug' );

	if ( ! is_string( $language ) || '' === $language ) {
		return $post;
	}

	$translated_id = (int) pll_get_post( $post->ID, $language );

	if ( $translated_id <= 0 ) {
		return $post;
	}

	$translated = get_post( $translated_id );

	if ( $translated instanceof WP_Post && 'publish' === $translated->post_status ) {
		return $translated;
	}

	return $post;
}

/**
 * Language of a navigation post, for hyphenation of its stored labels.
 *
 * Without Polylang the public menu is Finnish, so the labels hyphenate as Finnish.
 *
 * @param int $post_id Navigation post ID.
 */
function lohkareet_footer_navigation_lang( $post_id ) {
	if ( function_exists( 'pll_get_post_language' ) ) {
		$language = pll_get_post_language( (int) $post_id, 'slug' );

		if ( is_string( $language ) && preg_match( '/^[a-z]{2}$/', $language ) ) {
			return $language;
		}
	}

	return 'fi';
}

/**
 * Resolves one Navigation Link to a public URL.
 *
 * Page links use the post permalink. A root-relative custom URL is passed
 * through home_url(). Draft targets are skipped.
 *
 * @param array<string, mixed> $attrs Block attributes.
 */
function lohkareet_footer_navigation_url( $attrs ) {
	$kind = isset( $attrs['kind'] ) ? (string) $attrs['kind'] : '';
	$id   = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
	$type = isset( $attrs['type'] ) ? (string) $attrs['type'] : '';

	if ( 'post-type-archive' === $kind && 'event' === $type ) {
		$archive = get_post_type_archive_link( 'event' );

		if ( is_string( $archive ) && '' !== $archive ) {
			return $archive;
		}
	}

	if ( 'post-type' === $kind && $id > 0 && 'publish' === get_post_status( $id ) ) {
		$permalink = get_permalink( $id );

		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}
	}

	$url = isset( $attrs['url'] ) ? trim( (string) $attrs['url'] ) : '';

	if ( '' === $url ) {
		return '';
	}

	if ( '/' === substr( $url, 0, 1 ) && '/' !== substr( $url, 1, 1 ) ) {
		return home_url( $url );
	}

	return $url;
}

/**
 * Whether a menu URL is the page being viewed.
 *
 * @param string $url Absolute or site URL.
 */
function lohkareet_footer_request_matches( $url ) {
	$target = wp_parse_url( $url, PHP_URL_PATH );
	$target = is_string( $target ) ? untrailingslashit( $target ) : '';

	if ( '' === $target || '/' === $target ) {
		return is_front_page();
	}

	global $wp;

	$request = ( isset( $wp->request ) && is_string( $wp->request ) ) ? $wp->request : '';
	$here    = wp_parse_url( home_url( $request ), PHP_URL_PATH );
	$here    = is_string( $here ) ? untrailingslashit( $here ) : '';

	return '' !== $here && $here === $target;
}

/**
 * Footer index: the front page, then each published Navigation link.
 *
 * @return array<int, array{label: string, url: string, lang: string, current: bool}>
 */
function lohkareet_get_footer_navigation_links() {
	$home = array(
		array(
			'label'   => __( 'Home', 'lohkareet' ),
			'url'     => lohkareet_get_front_page_url(),
			'lang'    => lohkareet_get_current_language(),
			'current' => is_front_page(),
		),
	);

	$post = lohkareet_get_footer_navigation_post();

	if ( ! $post instanceof WP_Post ) {
		return $home;
	}

	$lang  = lohkareet_footer_navigation_lang( $post->ID );
	$links = array();

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( ! is_array( $block ) || 'core/navigation-link' !== ( isset( $block['blockName'] ) ? $block['blockName'] : '' ) ) {
			continue;
		}

		$attrs = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();
		$label = isset( $attrs['label'] ) ? trim( wp_strip_all_tags( (string) $attrs['label'] ) ) : '';
		$url   = lohkareet_footer_navigation_url( $attrs );

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$links[] = array(
			'label'   => $label,
			'url'     => $url,
			'lang'    => $lang,
			'current' => lohkareet_footer_request_matches( $url ),
		);
	}

	return array_merge( $home, $links );
}

/**
 * Published page with this slug, ignoring language query filters.
 *
 * @param string $slug Page slug.
 * @return WP_Post|null
 */
function lohkareet_get_published_page_by_slug( $slug ) {
	$posts = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	if ( ! $posts || ! isset( $posts[0] ) || ! $posts[0] instanceof WP_Post ) {
		return null;
	}

	return $posts[0];
}

/**
 * A page and its translations, when Polylang has linked any.
 *
 * @param int $page_id Page ID.
 * @return int[]
 */
function lohkareet_navigation_page_family_ids( $page_id ) {
	$ids = array( (int) $page_id );

	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( (int) $page_id );

		if ( is_array( $translations ) ) {
			foreach ( $translations as $translated_id ) {
				$ids[] = (int) $translated_id;
			}
		}
	}

	$ids = array_filter( $ids );

	return array_values( array_unique( $ids ) );
}

/**
 * Pages the header must be able to reach: Lohkareista and Varustautuminen.
 *
 * @return array<string, WP_Post>
 */
function lohkareet_canonical_navigation_pages() {
	$pages = array();

	foreach ( array( 'lohkareista', 'varustautuminen' ) as $slug ) {
		$page = lohkareet_get_published_page_by_slug( $slug );

		if ( $page instanceof WP_Post ) {
			$pages[ $slug ] = $page;
		}
	}

	return $pages;
}

/**
 * Last path segment of a menu URL.
 *
 * @param string $url Menu URL.
 */
function lohkareet_navigation_url_slug( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	$path = is_string( $path ) ? trim( $path, '/' ) : '';

	if ( '' === $path ) {
		return '';
	}

	$parts = explode( '/', $path );

	return (string) end( $parts );
}

/**
 * Whether one stored link points at the event archive.
 *
 * @param array<string, mixed> $attrs Block attributes.
 */
function lohkareet_navigation_link_is_event_archive( $attrs ) {
	$kind = isset( $attrs['kind'] ) ? (string) $attrs['kind'] : '';
	$type = isset( $attrs['type'] ) ? (string) $attrs['type'] : '';

	if ( 'post-type-archive' === $kind && 'event' === $type ) {
		return true;
	}

	$url  = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';
	$slug = lohkareet_navigation_url_slug( $url );

	return 'tapahtumat' === $slug;
}

/**
 * Whether one stored link points at a canonical page, by ID or by slug.
 *
 * A stale page ID still counts when the stored URL slug is the page slug.
 * That is how an imported menu can list Varustautuminen while core hides it.
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @param string               $slug  Page slug.
 * @param WP_Post              $page  Published page.
 */
function lohkareet_navigation_link_matches_page( $attrs, $slug, $page ) {
	$id = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;

	if ( in_array( $id, lohkareet_navigation_page_family_ids( $page->ID ), true ) ) {
		return true;
	}

	$url = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';

	return lohkareet_navigation_url_slug( $url ) === $slug;
}

/**
 * How many canonical destinations a navigation post actually contains.
 *
 * @param WP_Post $post Navigation post.
 */
function lohkareet_score_navigation_post( $post ) {
	$found = array();
	$pages = lohkareet_canonical_navigation_pages();

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( ! is_array( $block ) || 'core/navigation-link' !== ( isset( $block['blockName'] ) ? $block['blockName'] : '' ) ) {
			continue;
		}

		$attrs = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();

		if ( lohkareet_navigation_link_is_event_archive( $attrs ) ) {
			$found['archive'] = true;
		}

		foreach ( $pages as $slug => $page ) {
			if ( lohkareet_navigation_link_matches_page( $attrs, $slug, $page ) ) {
				$found[ $slug ] = true;
			}
		}
	}

	return count( $found );
}

/**
 * Navigation post the header should render.
 *
 * The Site Editor can hold a newer menu than the fixture marked
 * `_lohkareet_primary_navigation`. The header follows the published menu
 * that contains the event archive, Lohkareista, and Varustautuminen.
 * The footer keeps its own post.
 *
 * @return WP_Post|null
 */
function lohkareet_get_header_navigation_post() {
	static $resolved = false;
	static $chosen   = null;

	if ( $resolved ) {
		return $chosen instanceof WP_Post ? $chosen : null;
	}

	$resolved = true;
	$posts    = get_posts(
		array(
			'post_type'        => 'wp_navigation',
			'post_status'      => 'publish',
			'posts_per_page'   => 20,
			'orderby'          => 'modified',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	$best_score       = 0;
	$current_language = '';
	$chosen_language  = '';

	if ( function_exists( 'pll_current_language' ) ) {
		$language = pll_current_language( 'slug' );
		$current_language = is_string( $language ) ? $language : '';
	}

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$score         = lohkareet_score_navigation_post( $post );
		$post_language = '';

		if ( function_exists( 'pll_get_post_language' ) ) {
			$language      = pll_get_post_language( $post->ID, 'slug' );
			$post_language = is_string( $language ) ? $language : '';
		}

		$matches_language = '' !== $current_language && $post_language === $current_language;
		$chosen_matches   = '' !== $current_language && $chosen_language === $current_language;
		$same_score       = $score === $best_score && $score > 0 && $matches_language && ! $chosen_matches;

		if ( $score > $best_score || $same_score ) {
			$chosen          = $post;
			$best_score      = $score;
			$chosen_language = $post_language;
		}
	}

	if ( ! $chosen instanceof WP_Post ) {
		$chosen = lohkareet_get_footer_navigation_post();
	}

	return $chosen instanceof WP_Post ? $chosen : null;
}

/**
 * Page ID to show for this link in the current language.
 *
 * A missing translation keeps the original page. It is not removed.
 *
 * @param int $page_id Page ID.
 */
function lohkareet_navigation_page_for_current_language( $page_id ) {
	$page_id = (int) $page_id;

	if ( $page_id <= 0 || ! function_exists( 'pll_get_post' ) || ! function_exists( 'pll_current_language' ) ) {
		return $page_id;
	}

	$language = pll_current_language( 'slug' );

	if ( ! is_string( $language ) || '' === $language ) {
		return $page_id;
	}

	$translated_id = (int) pll_get_post( $page_id, $language );

	if ( $translated_id > 0 && 'publish' === get_post_status( $translated_id ) ) {
		return $translated_id;
	}

	return $page_id;
}

/**
 * Repairs a header link before WordPress decides whether it can render.
 *
 * Core hides a page link whose stored ID is missing or not published.
 * The slug in the URL is enough to find the current page. The current
 * language's translation is used when one exists.
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function lohkareet_prepare_header_navigation_link( $attrs ) {
	$kind = isset( $attrs['kind'] ) ? (string) $attrs['kind'] : '';
	$type = isset( $attrs['type'] ) ? (string) $attrs['type'] : '';

	if ( 'post-type-archive' === $kind && 'event' === $type ) {
		$archive = get_post_type_archive_link( 'event' );

		if ( is_string( $archive ) && '' !== $archive ) {
			$attrs['url'] = $archive;
		}

		return $attrs;
	}

	$id        = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
	$published = $id > 0 && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id );

	if ( ! $published ) {
		$url  = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';
		$slug = lohkareet_navigation_url_slug( $url );
		$page = '' !== $slug ? lohkareet_get_published_page_by_slug( $slug ) : null;

		if ( $page instanceof WP_Post ) {
			$attrs['id']   = $page->ID;
			$attrs['kind'] = 'post-type';
			$attrs['type'] = 'page';
			$id            = (int) $page->ID;
			$published     = true;
		}
	}

	if ( ! $published ) {
		$url = lohkareet_footer_navigation_url( $attrs );

		if ( '' !== $url ) {
			$attrs['url'] = $url;
		}

		return $attrs;
	}

	$attrs['id']  = lohkareet_navigation_page_for_current_language( $id );
	$permalink    = get_permalink( (int) $attrs['id'] );
	$attrs['url'] = is_string( $permalink ) && '' !== $permalink ? $permalink : ( isset( $attrs['url'] ) ? (string) $attrs['url'] : '' );

	return $attrs;
}

/**
 * Whether this link is the front page in any language.
 *
 * The header already has a Home link. Desktop hides that link because the
 * site mark goes home. Keeping a second front-page item would show Etusivu
 * on desktop and twice on mobile.
 *
 * @param array<string, mixed> $attrs Block attributes.
 */
function lohkareet_navigation_link_is_front_page( $attrs ) {
	$front_id = (int) get_option( 'page_on_front' );

	if ( $front_id <= 0 || 'page' !== get_option( 'show_on_front' ) ) {
		return false;
	}

	$id = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;

	if ( in_array( $id, lohkareet_navigation_page_family_ids( $front_id ), true ) ) {
		return true;
	}

	$url = isset( $attrs['url'] ) ? (string) $attrs['url'] : '';

	if ( '' === $url ) {
		return false;
	}

	$target = wp_parse_url( $url, PHP_URL_PATH );
	$target = is_string( $target ) ? untrailingslashit( $target ) : '';

	foreach ( lohkareet_navigation_page_family_ids( $front_id ) as $page_id ) {
		$permalink = get_permalink( $page_id );

		if ( ! is_string( $permalink ) || '' === $permalink ) {
			continue;
		}

		$path = wp_parse_url( $permalink, PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';

		if ( '' !== $target && $target === $path ) {
			return true;
		}
	}

	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home = is_string( $home ) ? untrailingslashit( $home ) : '';

	return $target === $home;
}

/**
 * Binds the header Navigation block to the menu that contains the real destinations.
 *
 * A saved template reference to an older menu is replaced. Page links are
 * repaired before core drops an item whose stored ID no longer exists.
 *
 * @param array<string, mixed> $parsed_block Parsed block.
 * @return array<string, mixed>
 */
function lohkareet_bind_navigation_blocks( $parsed_block ) {
	if ( ! is_array( $parsed_block ) ) {
		return $parsed_block;
	}

	$name = isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';

	if ( 'core/navigation' === $name ) {
		$post = lohkareet_get_header_navigation_post();

		if ( $post instanceof WP_Post ) {
			if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
				$parsed_block['attrs'] = array();
			}

			$parsed_block['attrs']['ref'] = $post->ID;
		}
	}

	if ( 'core/navigation-link' === $name ) {
		$attrs = ( isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ) ? $parsed_block['attrs'] : array();

		if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
			$parsed_block['attrs'] = array();
		}

		$parsed_block['attrs'] = lohkareet_prepare_header_navigation_link( $attrs );
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'lohkareet_bind_navigation_blocks' );

/**
 * Repairs header links after WordPress has loaded them from the Navigation post.
 *
 * The Navigation block renders those links itself. The usual block data
 * filter does not run on them first, so a stale page ID is corrected here,
 * before core can drop the item.
 *
 * @param WP_Block_List $inner_blocks Navigation items.
 * @return WP_Block_List
 */
function lohkareet_prepare_header_navigation_inner_blocks( $inner_blocks ) {
	if ( ! $inner_blocks instanceof WP_Block_List ) {
		return $inner_blocks;
	}

	$parsed = array();

	foreach ( $inner_blocks as $block ) {
		if ( $block instanceof WP_Block && is_array( $block->parsed_block ) ) {
			$parsed[] = lohkareet_prepare_navigation_block_tree( $block->parsed_block );
		}
	}

	return new WP_Block_List( $parsed );
}
add_filter( 'block_core_navigation_render_inner_blocks', 'lohkareet_prepare_header_navigation_inner_blocks' );

/**
 * Repairs one navigation block and any nested items.
 *
 * @param array<string, mixed> $block Parsed block.
 * @return array<string, mixed>
 */
function lohkareet_prepare_navigation_block_tree( $block ) {
	$name = isset( $block['blockName'] ) ? $block['blockName'] : '';

	if ( 'core/navigation-link' === $name ) {
		$attrs          = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();
		$block['attrs'] = lohkareet_prepare_header_navigation_link( $attrs );
	}

	if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $index => $child ) {
			if ( is_array( $child ) ) {
				$block['innerBlocks'][ $index ] = lohkareet_prepare_navigation_block_tree( $child );
			}
		}
	}

	return $block;
}

/**
 * Leaves the front page out of the Navigation block.
 *
 * Home stays on the separate header link, which desktop hides.
 *
 * @param string               $html  Rendered link.
 * @param array<string, mixed> $block Parsed block.
 */
function lohkareet_omit_front_page_navigation_link( $html, $block ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $html;
	}

	$attrs = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();

	if ( lohkareet_navigation_link_is_front_page( $attrs ) ) {
		return '';
	}

	return $html;
}
add_filter( 'render_block_core/navigation-link', 'lohkareet_omit_front_page_navigation_link', 10, 2 );

/**
 * Direct language links, or an empty string when fewer than two languages exist.
 */
function lohkareet_get_footer_language_markup() {
	if ( ! function_exists( 'lohkareet_get_language_switcher_items' ) ) {
		return '';
	}

	$items = lohkareet_get_language_switcher_items();

	if ( count( $items ) < 2 ) {
		return '';
	}

	$html = '<nav class="lohkareet-footer__languages" aria-label="' . esc_attr__( 'Footer language', 'lohkareet' ) . '"><ul>';

	foreach ( $items as $item ) {
		$label    = strtoupper( $item['slug'] );
		$hreflang = str_replace( '_', '-', $item['locale'] );

		if ( $item['current'] ) {
			$html .= '<li class="is-current"><span aria-current="true" lang="' . esc_attr( $item['slug'] ) . '">' . esc_html( $label ) . '</span></li>';
			continue;
		}

		$html .= '<li><a href="' . esc_url( $item['url'] ) . '" hreflang="' . esc_attr( $hreflang ) . '" lang="' . esc_attr( $item['slug'] ) . '">' . esc_html( $label ) . '</a></li>';
	}

	$html .= '</ul></nav>';

	return $html;
}

/**
 * Privacy policy URL when that page is published.
 *
 * A draft policy stays out of the footer.
 */
function lohkareet_get_published_privacy_url() {
	$page_id = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( $page_id <= 0 || 'publish' !== get_post_status( $page_id ) ) {
		return '';
	}

	$url = get_permalink( $page_id );

	return is_string( $url ) ? $url : '';
}

/**
 * Renders the footer composition.
 */
function lohkareet_render_site_footer() {
	$links   = lohkareet_get_footer_navigation_links();
	$home    = lohkareet_get_front_page_url();
	$privacy = lohkareet_get_published_privacy_url();
	$year    = wp_date( 'Y' );

	$nav = '<ul>';

	foreach ( $links as $link ) {
		$attrs = ' href="' . esc_url( $link['url'] ) . '" lang="' . esc_attr( $link['lang'] ) . '"';

		if ( $link['current'] ) {
			$attrs .= ' aria-current="page"';
		}

		$nav .= '<li><a' . $attrs . '>' . esc_html( $link['label'] ) . '</a></li>';
	}

	$nav .= '</ul>';

	$html  = '<div ' . get_block_wrapper_attributes( array( 'class' => 'lohkareet-footer' ) ) . '>';
	$html .= '<div class="lohkareet-footer__grid">';
	$html .= '<p class="lohkareet-footer__identity" lang="fi">';
	$html .= '<span class="lohkareet-footer__line">' . esc_html__( 'Siirtymä', 'lohkareet' ) . '</span>';
	$html .= '<a class="lohkareet-footer__line" href="' . esc_url( $home ) . '">' . esc_html__( 'Lohkareet', 'lohkareet' ) . '</a>';
	$html .= '</p>';
	$html .= '<nav class="lohkareet-footer__nav" aria-label="' . esc_attr__( 'Footer', 'lohkareet' ) . '">' . $nav . '</nav>';
	$html .= '<div class="lohkareet-footer__credit">';
	$html .= '<a href="' . esc_url( 'https://ambientry.fi/' ) . '">';
	$html .= '<img src="' . esc_url( get_theme_file_uri( 'assets/images/ambientry-logo.svg' ) ) . '" alt="' . esc_attr__( 'Suomen Ambientyhdistys ry', 'lohkareet' ) . '" width="43" height="27" decoding="async" />';
	$html .= '</a>';
	$html .= '<a href="' . esc_url( 'https://www.operaatiopirkanmaa.fi/' ) . '">';
	$html .= '<img src="' . esc_url( get_theme_file_uri( 'assets/images/operaatio_pirkanmaa-logo-white.png' ) ) . '" alt="' . esc_attr__( 'Operaatio Pirkanmaa', 'lohkareet' ) . '" width="1000" height="559" decoding="async" />';
	$html .= '</a>';
	$html .= '</div>';
	$html .= '<div class="lohkareet-footer__colophon">';
	$html .= lohkareet_get_footer_language_markup();

	if ( '' !== $privacy ) {
		$html .= '<a class="lohkareet-footer__privacy" href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy', 'lohkareet' ) . '</a>';
	}

	$html .= '<p class="lohkareet-footer__copyright">© ' . esc_html( $year ) . ' ' . esc_html__( 'Suomen Ambientyhdistys ry', 'lohkareet' ) . '</p>';
	$html .= '</div></div>';
	$html .= lohkareet_get_municipality_logo_markup();
	$html .= '</div>';

	return $html;
}

/**
 * White municipality marks, in program order.
 *
 * --logo-fit enlarges a file whose artwork does not fill the canvas, so the
 * visible mark shares one height. The files stay in the theme.
 *
 * @return array<int, array{file: string, name: string, width: int, height: int, fit: string}>
 */
function lohkareet_get_municipality_logos() {
	return array(
		array(
			'file'   => 'punkalaidun.png',
			'name'   => 'Punkalaidun',
			'width'  => 165,
			'height' => 25,
			'fit'    => '1.19',
		),
		array(
			'file'   => 'nokia.png',
			'name'   => 'Nokia',
			'width'  => 110,
			'height' => 116,
			'fit'    => '3.6',
		),
		array(
			'file'   => 'sastamala.png',
			'name'   => 'Sastamala',
			'width'  => 135,
			'height' => 35,
			'fit'    => '1',
		),
		array(
			'file'   => 'hameenkyro.png',
			'name'   => 'Hämeenkyrö',
			'width'  => 125,
			'height' => 34,
			'fit'    => '1',
		),
		array(
			'file'   => 'ylojarvi.png',
			'name'   => 'Ylöjärvi',
			'width'  => 135,
			'height' => 28,
			'fit'    => '1.333',
		),
		array(
			'file'   => 'valkeakoski.png',
			'name'   => 'Valkeakoski',
			'width'  => 192,
			'height' => 34,
			'fit'    => '1',
		),
		array(
			'file'   => 'pirkkala.png',
			'name'   => 'Pirkkala',
			'width'  => 137,
			'height' => 37,
			'fit'    => '1',
		),
		array(
			'file'   => 'lempaala.png',
			'name'   => 'Lempäälä',
			'width'  => 103,
			'height' => 39,
			'fit'    => '1',
		),
		array(
			'file'   => 'palkane.png',
			'name'   => 'Pälkäne',
			'width'  => 135,
			'height' => 36,
			'fit'    => '1.286',
		),
	);
}

/**
 * Closing row of municipality marks.
 *
 * Each mark links to the one published event in the current language whose
 * lohkareet_municipality value matches the logo name. Without that unique
 * event the mark stays an image.
 *
 * @return string
 */
function lohkareet_get_municipality_logo_markup() {
	$html = '<ul class="lohkareet-footer__municipalities">';

	foreach ( lohkareet_get_municipality_logos() as $logo ) {
		$image = lohkareet_get_municipality_logo_image( $logo );
		$url   = lohkareet_get_municipality_event_url( $logo['name'] );

		$html .= '<li>';

		if ( '' !== $url ) {
			$html .= '<a href="' . esc_url( $url ) . '">' . $image . '</a>';
		} else {
			$html .= $image;
		}

		$html .= '</li>';
	}

	$html .= '</ul>';

	return $html;
}

/**
 * Permalink of the current language's published event for one municipality.
 *
 * The name is compared exactly with lohkareet_municipality. Zero matches and
 * more than one match both return an empty string, so the logo is not sent
 * to another municipality's page.
 *
 * @param string $name Municipality name used by the logo.
 * @return string
 */
function lohkareet_get_municipality_event_url( $name ) {
	if ( ! is_string( $name ) || '' === $name ) {
		return '';
	}

	$matches = array();

	foreach ( lohkareet_get_events() as $event ) {
		if ( ! $event instanceof WP_Post ) {
			continue;
		}

		if ( lohkareet_get_event_meta_string( $event->ID, 'lohkareet_municipality' ) !== $name ) {
			continue;
		}

		$matches[] = $event;
	}

	if ( 1 !== count( $matches ) ) {
		return '';
	}

	$url = get_permalink( $matches[0] );

	return is_string( $url ) ? $url : '';
}

/**
 * One municipality mark, matched by the shared municipality name.
 *
 * @param string $name Municipality name.
 * @return array{file: string, name: string, width: int, height: int, fit: string}|null
 */
function lohkareet_get_municipality_logo( $name ) {
	$name = is_string( $name ) ? $name : '';

	foreach ( lohkareet_get_municipality_logos() as $logo ) {
		if ( $logo['name'] === $name ) {
			return $logo;
		}
	}

	return null;
}

/**
 * Image element for one municipality mark.
 *
 * @param array{file: string, name: string, width: int, height: int, fit: string} $logo Logo data.
 */
function lohkareet_get_municipality_logo_image( $logo ) {
	$src = get_theme_file_uri( 'assets/images/' . $logo['file'] );

	return sprintf(
		'<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" style="--logo-fit:%5$s" decoding="async" />',
		esc_url( $src ),
		esc_attr( $logo['name'] ),
		(int) $logo['width'],
		(int) $logo['height'],
		esc_attr( $logo['fit'] )
	);
}
