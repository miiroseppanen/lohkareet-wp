<?php
/**
 * Event custom post type.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the event post type.
 *
 * The archive base is /tapahtumat/. A multilingual plugin may translate that
 * slug. This theme does not add language prefixes of its own.
 */
function lohkareet_register_event_post_type() {
	$labels = array(
		'name'                  => __( 'Events', 'lohkareet' ),
		'singular_name'         => __( 'Event', 'lohkareet' ),
		'add_new'               => __( 'Add New', 'lohkareet' ),
		'add_new_item'          => __( 'Add New Event', 'lohkareet' ),
		'edit_item'             => __( 'Edit Event', 'lohkareet' ),
		'new_item'              => __( 'New Event', 'lohkareet' ),
		'view_item'             => __( 'View Event', 'lohkareet' ),
		'view_items'            => __( 'View Events', 'lohkareet' ),
		'search_items'          => __( 'Search Events', 'lohkareet' ),
		'not_found'             => __( 'No events found.', 'lohkareet' ),
		'not_found_in_trash'    => __( 'No events found in Trash.', 'lohkareet' ),
		'all_items'             => __( 'All Events', 'lohkareet' ),
		'archives'              => __( 'Event archives', 'lohkareet' ),
		'menu_name'             => __( 'Events', 'lohkareet' ),
		'featured_image'        => __( 'Featured image', 'lohkareet' ),
		'set_featured_image'    => __( 'Set featured image', 'lohkareet' ),
		'remove_featured_image' => __( 'Remove featured image', 'lohkareet' ),
		'use_featured_image'    => __( 'Use as featured image', 'lohkareet' ),
		'item_published'        => __( 'Event published.', 'lohkareet' ),
		'item_updated'          => __( 'Event updated.', 'lohkareet' ),
	);

	register_post_type(
		'event',
		array(
			'labels'             => $labels,
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-location',
			'menu_position'      => 21,
			'has_archive'        => 'tapahtumat',
			'rewrite'            => array(
				'slug'       => 'tapahtumat',
				'with_front' => false,
			),
			'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capability_type'     => 'post',
			'exclude_from_search' => false,
		)
	);
}
add_action( 'init', 'lohkareet_register_event_post_type' );

/**
 * Flushes rewrite rules when the theme is activated.
 */
function lohkareet_flush_event_rewrites() {
	lohkareet_register_event_post_type();
	flush_rewrite_rules();
	update_option( 'lohkareet_rewrite_version', '1' );
}
add_action( 'after_switch_theme', 'lohkareet_flush_event_rewrites' );

/**
 * Flushes permalinks once after the event type is introduced.
 *
 * Copying the theme onto an already active site does not fire the switch hook.
 */
function lohkareet_maybe_flush_event_rewrites() {
	if ( '1' === get_option( 'lohkareet_rewrite_version' ) ) {
		return;
	}

	flush_rewrite_rules();
	update_option( 'lohkareet_rewrite_version', '1' );
}
add_action( 'init', 'lohkareet_maybe_flush_event_rewrites', 20 );

/**
 * Adds municipality and date columns to the events list.
 *
 * @param string[] $columns Existing columns.
 * @return string[]
 */
function lohkareet_event_admin_columns( $columns ) {
	$next = array();

	foreach ( $columns as $key => $label ) {
		$next[ $key ] = $label;

		if ( 'title' === $key ) {
			$next['lohkareet_municipality'] = __( 'Municipality', 'lohkareet' );
			$next['lohkareet_event_date']   = __( 'Date', 'lohkareet' );
		}
	}

	return $next;
}
add_filter( 'manage_event_posts_columns', 'lohkareet_event_admin_columns' );

/**
 * Renders a custom events-list column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Event ID.
 */
function lohkareet_event_admin_column( $column, $post_id ) {
	if ( 'lohkareet_municipality' === $column ) {
		$municipality = lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' );
		echo '' !== $municipality ? esc_html( $municipality ) : esc_html__( 'Unknown', 'lohkareet' );
		return;
	}

	if ( 'lohkareet_event_date' === $column ) {
		$date = lohkareet_get_event_date_display( $post_id );
		echo '' !== $date ? esc_html( $date ) : esc_html__( 'Unknown', 'lohkareet' );
	}
}
add_action( 'manage_event_posts_custom_column', 'lohkareet_event_admin_column', 10, 2 );
