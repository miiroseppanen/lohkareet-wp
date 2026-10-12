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
 * Uses the same Navigation post in the header that the footer reads.
 *
 * An empty Navigation block would otherwise fall back to a page list.
 * Page and event-archive URLs are resolved when the block renders.
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
		$ref = isset( $parsed_block['attrs']['ref'] ) ? (int) $parsed_block['attrs']['ref'] : 0;

		if ( $ref <= 0 ) {
			$post = lohkareet_get_footer_navigation_post();

			if ( $post instanceof WP_Post ) {
				if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
					$parsed_block['attrs'] = array();
				}

				$parsed_block['attrs']['ref'] = $post->ID;
			}
		}
	}

	if ( 'core/navigation-link' === $name ) {
		$attrs = ( isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ) ? $parsed_block['attrs'] : array();
		$url   = lohkareet_footer_navigation_url( $attrs );

		if ( '' !== $url ) {
			if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
				$parsed_block['attrs'] = array();
			}

			$parsed_block['attrs']['url'] = $url;
		}
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'lohkareet_bind_navigation_blocks' );

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
	$html .= '<img src="' . esc_url( get_theme_file_uri( 'assets/images/ambientry-logo.svg' ) ) . '" alt="' . esc_attr__( 'Suomen Ambientyhdistys ry', 'lohkareet' ) . '" width="43" height="27" decoding="async" />';
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
			'fit'    => '1.415',
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
 * Closing row of municipality marks. They are not links.
 */
function lohkareet_get_municipality_logo_markup() {
	$html = '<ul class="lohkareet-footer__municipalities">';

	foreach ( lohkareet_get_municipality_logos() as $logo ) {
		$src = get_theme_file_uri( 'assets/images/' . $logo['file'] );

		$html .= '<li>';
		$html .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $logo['name'] ) . '" width="' . (int) $logo['width'] . '" height="' . (int) $logo['height'] . '" style="--logo-fit:' . esc_attr( $logo['fit'] ) . '" decoding="async" />';
		$html .= '</li>';
	}

	$html .= '</ul>';

	return $html;
}
