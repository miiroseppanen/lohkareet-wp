<?php
/**
 * Lohkareet functions and definitions.
 *
 * Global styles and block-theme supports come from theme.json and WordPress core.
 * Event data, templates, and the map live in the files loaded below.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_theme_file_path( 'inc/i18n.php' );
require get_theme_file_path( 'inc/event-post-type.php' );
require get_theme_file_path( 'inc/event-helpers.php' );
require get_theme_file_path( 'inc/event-meta.php' );
require get_theme_file_path( 'inc/works.php' );
require get_theme_file_path( 'inc/blocks.php' );
require get_theme_file_path( 'inc/hero.php' );
require get_theme_file_path( 'inc/navigation.php' );

/**
 * Loads theme translations for the active WordPress locale.
 *
 * Finnish UI strings live in languages/fi.l10n.php because the standard
 * Finnish locale is `fi`. This does not change the site locale. An English
 * site keeps the English strings written in the theme.
 */
function lohkareet_setup() {
	load_theme_textdomain( 'lohkareet', get_template_directory() . '/languages' );
	add_editor_style( 'assets/css/theme.css' );
	add_editor_style( 'assets/css/layout.css' );
}

/**
 * Adds the page slug as a layout hook.
 *
 * Editorial placements use this class. They do not depend on a copied template.
 *
 * @param string[] $classes Body classes.
 */
function lohkareet_body_class( $classes ) {
	if ( ! is_singular() ) {
		return $classes;
	}

	$post = get_queried_object();

	if ( $post instanceof WP_Post && '' !== $post->post_name ) {
		$classes[] = 'lohkareet-entry-' . sanitize_html_class( $post->post_name );
	}

	return $classes;
}
add_filter( 'body_class', 'lohkareet_body_class' );
add_action( 'after_setup_theme', 'lohkareet_setup' );

/**
 * Registers the Adobe Fonts kit that serves Presicav.
 *
 * The CSS family name in kit lgz0wqd is "presicav". This does not enqueue it.
 */
function lohkareet_register_typekit() {
	wp_register_style(
		'lohkareet-typekit',
		'https://use.typekit.net/lgz0wqd.css',
		array(),
		null
	);
}
add_action( 'init', 'lohkareet_register_typekit', 5 );

/**
 * Enqueues front-end CSS.
 *
 * The kit stylesheet is loaded here only. Do not import it from CSS.
 */
function lohkareet_enqueue_assets() {
	wp_enqueue_style( 'lohkareet-typekit' );

	wp_enqueue_style(
		'lohkareet-theme',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array( 'lohkareet-typekit' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'lohkareet-layout',
		get_theme_file_uri( 'assets/css/layout.css' ),
		array( 'lohkareet-theme' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'lohkareet_enqueue_assets' );

/**
 * Asks an administrator to use a static front page.
 *
 * front-page.html is used for the site front whether or not a page is assigned.
 * Page content is only correct when Settings → Reading uses a static page.
 */
function lohkareet_front_page_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'themes', 'options-reading' ), true ) ) {
		return;
	}

	if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'The Lohkareet home page works when Settings → Reading uses a static front page.', 'lohkareet' );
	echo ' <a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">';
	echo esc_html__( 'Open reading settings', 'lohkareet' );
	echo '</a></p></div>';
}
add_action( 'admin_notices', 'lohkareet_front_page_admin_notice' );
