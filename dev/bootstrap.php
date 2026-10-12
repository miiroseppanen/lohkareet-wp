<?php
/**
 * Rebuilds the known Lohkareet content into the local WordPress database.
 *
 * Running this again updates the fixture posts. It does not edit posts
 * that are not listed in site-content.php.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	$load = '/wordpress/wp-load.php';

	if ( ! is_readable( $load ) ) {
		fwrite( STDERR, "WordPress is not installed at /wordpress yet.\n" );
		exit( 1 );
	}

	require $load;
}

require __DIR__ . '/site-content.php';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Finds a fixture post by its stable key.
 *
 * @param string $post_type Post type.
 * @param string $key       Fixture key.
 */
function lohkareet_dev_find_post( $post_type, $key ) {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'   => 1,
			'meta_key'         => '_lohkareet_fixture',
			'meta_value'       => $key,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	if ( $posts && isset( $posts[0] ) && $posts[0] instanceof WP_Post ) {
		return $posts[0];
	}

	return null;
}

/**
 * Creates or updates one fixture post.
 *
 * @param string               $post_type Post type.
 * @param string               $key       Fixture key.
 * @param array<string, mixed> $fields    Post fields.
 */
function lohkareet_dev_upsert_post( $post_type, $key, $fields ) {
	$existing = lohkareet_dev_find_post( $post_type, $key );
	$fields['post_type']   = $post_type;
	$fields['post_status'] = 'publish';

	if ( $existing instanceof WP_Post ) {
		$fields['ID'] = $existing->ID;
		$result       = wp_update_post( $fields, true );
	} else {
		$result = wp_insert_post( $fields, true );
	}

	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, $result->get_error_message() . "\n" );
		exit( 1 );
	}

	update_post_meta( (int) $result, '_lohkareet_fixture', $key );

	return (int) $result;
}

/**
 * Writes one event meta value, or removes it when the fact is unknown.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Meta key.
 * @param string $value   Stored value.
 */
function lohkareet_dev_set_meta( $post_id, $key, $value ) {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
		return;
	}

	update_post_meta( $post_id, $key, $value );
}

/**
 * Imports one theme image as an attachment, once.
 *
 * @param string $filename File name inside theme/assets/images.
 */
function lohkareet_dev_import_image( $filename ) {
	$existing = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'posts_per_page'   => 1,
			'meta_key'         => '_lohkareet_source_file',
			'meta_value'       => $filename,
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	if ( $existing && isset( $existing[0] ) && $existing[0] instanceof WP_Post ) {
		return (int) $existing[0]->ID;
	}

	$source = '/wordpress/wp-content/themes/lohkareet/assets/images/' . $filename;

	if ( ! is_readable( $source ) ) {
		fwrite( STDERR, "Missing theme image: $filename\n" );
		return 0;
	}

	$tmp = wp_tempnam( $filename );

	if ( ! is_string( $tmp ) || ! copy( $source, $tmp ) ) {
		fwrite( STDERR, "Could not copy theme image: $filename\n" );
		return 0;
	}

	$file = array(
		'name'     => $filename,
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file, 0 );

	if ( is_wp_error( $attachment_id ) ) {
		fwrite( STDERR, $attachment_id->get_error_message() . "\n" );
		return 0;
	}

	update_post_meta( (int) $attachment_id, '_lohkareet_source_file', $filename );

	return (int) $attachment_id;
}

$content = lohkareet_dev_site_content();
$ids     = array();

switch_theme( 'lohkareet' );

$front_id = lohkareet_dev_upsert_post(
	'page',
	$content['front']['key'],
	array(
		'post_title'   => $content['front']['title'],
		'post_name'    => 'etusivu',
		'post_content' => $content['front']['content'],
	)
);
$ids[ $content['front']['key'] ] = $front_id;

foreach ( $content['pages'] as $page ) {
	$ids[ $page['key'] ] = lohkareet_dev_upsert_post(
		'page',
		$page['key'],
		array(
			'post_title'   => $page['title'],
			'post_name'    => $page['key'],
			'post_content' => $page['content'],
		)
	);
}

foreach ( $content['contributors'] as $contributor ) {
	$ids[ $contributor['key'] ] = lohkareet_dev_upsert_post(
		'contributor',
		$contributor['key'],
		array(
			'post_title'   => $contributor['name'],
			'post_content' => '',
		)
	);
}

foreach ( $content['works'] as $work ) {
	$work_id = lohkareet_dev_upsert_post(
		'work',
		$work['key'],
		array(
			'post_title'   => $work['title'],
			'post_content' => $work['content'],
		)
	);
	$ids[ $work['key'] ] = $work_id;
	$contributor_id      = isset( $ids[ $work['contributor'] ] ) ? (int) $ids[ $work['contributor'] ] : 0;
	lohkareet_dev_set_meta( $work_id, 'lohkareet_work_title', '' );
	lohkareet_dev_set_meta( $work_id, 'lohkareet_work_type', '' );
	lohkareet_dev_set_meta( $work_id, 'lohkareet_contributor_ids', $contributor_id > 0 ? (string) $contributor_id : '' );
}

foreach ( $content['events'] as $event ) {
	$event_id = lohkareet_dev_upsert_post(
		'event',
		$event['key'],
		array(
			'post_title'   => $event['title'],
			'post_name'    => $event['key'],
			'post_content' => '',
		)
	);

	lohkareet_dev_set_meta( $event_id, 'lohkareet_municipality', $event['municipality'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_date_year', $event['year'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_date_month', $event['month'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_date_day', $event['day'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_start_time', $event['start'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_end_time', $event['end'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_venue', $event['venue'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_artist_name', $event['artist'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_artist_status', $event['status'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_artist_description', $event['description'] );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_address', '' );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_location_description', '' );
	lohkareet_dev_set_meta( $event_id, 'lohkareet_map_url', '' );

	$image_id = 0;

	if ( '' !== $event['image'] ) {
		$image_id = lohkareet_dev_import_image( $event['image'] );
	}

	lohkareet_dev_set_meta( $event_id, 'lohkareet_artist_image', $image_id > 0 ? (string) $image_id : '' );

	$work_ids = array();

	foreach ( $event['works'] as $work_key ) {
		if ( isset( $ids[ $work_key ] ) ) {
			$work_ids[] = (int) $ids[ $work_key ];
		}
	}

	lohkareet_dev_set_meta( $event_id, 'lohkareet_work_ids', implode( ',', $work_ids ) );
}

flush_rewrite_rules( false );

$navigation = '';

foreach ( $content['navigation'] as $item ) {
	$attrs = array(
		'label' => $item['label'],
		'kind'  => $item['kind'],
		'type'  => $item['type'],
	);

	if ( 'post-type-archive' === $item['kind'] && 'event' === $item['type'] ) {
		$archive = get_post_type_archive_link( 'event' );

		if ( is_string( $archive ) ) {
			$attrs['url'] = $archive;
		}
	}

	if ( 'post-type' === $item['kind'] && isset( $item['key'], $ids[ $item['key'] ] ) ) {
		$attrs['id']  = (int) $ids[ $item['key'] ];
		$permalink    = get_permalink( $attrs['id'] );

		if ( is_string( $permalink ) ) {
			$attrs['url'] = $permalink;
		}
	}

	$navigation .= '<!-- wp:navigation-link ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . " /-->\n";
}

$navigation_id = lohkareet_dev_upsert_post(
	'wp_navigation',
	'primary',
	array(
		'post_title'   => 'Primary',
		'post_content' => $navigation,
	)
);
update_post_meta( $navigation_id, '_lohkareet_primary_navigation', '1' );

$others = get_posts(
	array(
		'post_type'        => 'wp_navigation',
		'post_status'      => array( 'publish', 'draft' ),
		'posts_per_page'   => 20,
		'post__not_in'     => array( $navigation_id ),
		'no_found_rows'    => true,
		'suppress_filters' => true,
	)
);

foreach ( $others as $other ) {
	if ( $other instanceof WP_Post ) {
		wp_trash_post( $other->ID );
	}
}

$sample = get_page_by_path( 'sample-page' );

if ( $sample instanceof WP_Post && 'trash' !== $sample->post_status ) {
	wp_trash_post( $sample->ID );
}

$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );

if ( $hello instanceof WP_Post && 'publish' === $hello->post_status ) {
	wp_update_post(
		array(
			'ID'          => $hello->ID,
			'post_status' => 'draft',
		)
	);
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $front_id );
update_option( 'page_for_posts', 0 );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'blogname', 'Lohkareet' );
update_option( 'timezone_string', 'Europe/Helsinki' );
update_option( 'date_format', 'j.n.Y' );
update_option( 'time_format', 'H:i' );
update_option( 'start_of_week', 1 );

if ( ! in_array( 'fi', get_available_languages(), true ) ) {
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';

	if ( function_exists( 'wp_download_language_pack' ) ) {
		wp_download_language_pack( 'fi' );
	}
}

if ( in_array( 'fi', get_available_languages(), true ) ) {
	update_option( 'WPLANG', 'fi' );
}

flush_rewrite_rules( false );

echo "Bootstrap complete.\n";
echo 'Front page: ' . $front_id . "\n";
echo 'Navigation: ' . $navigation_id . "\n";
echo 'Events: ' . count( $content['events'] ) . "\n";
