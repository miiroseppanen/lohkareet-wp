<?php
/**
 * Header navigation presentation.
 *
 * The links themselves stay in the Navigation block. Polylang translates that
 * menu. This file only adds the mobile open/close control.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the small header wordmark and the mobile menu button.
 */
function lohkareet_register_menu_toggle() {
	register_block_type(
		'lohkareet/site-mark',
		array(
			'api_version'     => 3,
			'title'           => __( 'Site mark', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_site_mark',
		)
	);

	register_block_type(
		'lohkareet/menu-home',
		array(
			'api_version'     => 3,
			'title'           => __( 'Home', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_menu_home',
		)
	);

	register_block_type(
		'lohkareet/menu-toggle',
		array(
			'api_version'     => 3,
			'title'           => __( 'Menu toggle', 'lohkareet' ),
			'category'        => 'lohkareet',
			'render_callback' => 'lohkareet_render_menu_toggle',
		)
	);
}
add_action( 'init', 'lohkareet_register_menu_toggle' );

/**
 * Renders the small LOHKAREET link in the header.
 *
 * The home URL comes from WordPress. It is not written into the template.
 */
function lohkareet_render_site_mark() {
	return '<a ' . get_block_wrapper_attributes(
		array(
			'class' => 'lohkareet-header__mark',
			'href'  => esc_url( lohkareet_get_front_page_url() ),
		)
	) . '>' . esc_html__( 'Lohkareet', 'lohkareet' ) . '</a>';
}

/**
 * Front-page URL for the current language.
 *
 * Polylang's home URL wins when the plugin is active. Otherwise this is the
 * static front page, then the site root.
 */
function lohkareet_get_front_page_url() {
	$front_id = (int) get_option( 'page_on_front' );
	$url      = '';

	if ( function_exists( 'pll_home_url' ) ) {
		$pll_url = pll_home_url();

		if ( is_string( $pll_url ) && '' !== $pll_url ) {
			$url = $pll_url;
		}
	}

	if ( '' === $url && $front_id && 'page' === get_option( 'show_on_front' ) ) {
		$permalink = get_permalink( $front_id );
		$url       = is_string( $permalink ) ? $permalink : '';
	}

	if ( '' === $url ) {
		$url = home_url( '/' );
	}

	return esc_url_raw( $url );
}

/**
 * Front-page link for the mobile menu.
 *
 * Desktop hides it. The small LOHKAREET mark already links home there.
 */
function lohkareet_render_menu_home() {
	$url = lohkareet_get_front_page_url();

	$attrs = array(
		'class' => 'lohkareet-header__home',
		'href'  => $url,
	);

	if ( is_front_page() ) {
		$attrs['aria-current'] = 'page';
	}

	return '<a ' . get_block_wrapper_attributes( $attrs ) . '><span class="lohkareet-header__home-label">' . esc_html__( 'Home', 'lohkareet' ) . '</span></a>';
}

/**
 * Renders the mobile menu button. Desktop CSS hides it.
 */
function lohkareet_render_menu_toggle() {
	$open  = __( 'Menu', 'lohkareet' );
	$close = __( 'Close', 'lohkareet' );

	return '<button ' . get_block_wrapper_attributes(
		array(
			'class'            => 'lohkareet-header__toggle',
			'type'             => 'button',
			'aria-expanded'    => 'false',
			'aria-controls'    => 'lohkareet-menu',
			'data-label-open'  => $open,
			'data-label-close' => $close,
		)
	) . '><span class="lohkareet-header__toggle-text">' . esc_html( $open ) . '</span></button>';
}

/**
 * Marks that navigation JavaScript is available before the first paint.
 */
function lohkareet_navigation_js_flag() {
	echo "<script>document.documentElement.classList.add('lohkareet-js');</script>\n";
}
add_action( 'wp_head', 'lohkareet_navigation_js_flag', 0 );

/**
 * Loads the navigation stylesheet and the mobile menu script on the public site.
 */
function lohkareet_enqueue_navigation() {
	wp_enqueue_style(
		'lohkareet-navigation',
		get_theme_file_uri( 'assets/css/navigation.css' ),
		array( 'lohkareet-layout' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'lohkareet-navigation',
		get_theme_file_uri( 'assets/js/navigation.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'lohkareet_enqueue_navigation', 20 );

/**
 * Loads navigation CSS in the block editor canvas.
 */
function lohkareet_navigation_editor_style() {
	add_editor_style( 'assets/css/navigation.css' );
}
add_action( 'after_setup_theme', 'lohkareet_navigation_editor_style' );
