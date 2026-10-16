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

	if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		wp_enqueue_script( 'lohkareet-hero-video' );
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
	$html .= '</div></div></div>';

	return $html;
}
