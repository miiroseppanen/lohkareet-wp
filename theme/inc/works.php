<?php
/**
 * Recurring contributors and the works assigned to events.
 *
 * A performing artist belongs to one event. A work can be assigned to many
 * events, so the contributor, the work text, and the assignment are stored
 * separately. Copying the same biography into every event would make several
 * sources of truth.
 *
 * Contributor names stay on the original-language post. Descriptions and the
 * work type are post content and meta, so Polylang can translate them without
 * a second set of technical fields.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers contributors and works.
 *
 * Neither has a public URL. They are edited in wp-admin and rendered on the
 * events they are assigned to.
 */
function lohkareet_register_work_post_types() {
	register_post_type(
		'contributor',
		array(
			'labels'              => array(
				'name'          => __( 'Contributors', 'lohkareet' ),
				'singular_name' => __( 'Contributor', 'lohkareet' ),
				'add_new_item'  => __( 'Add New Contributor', 'lohkareet' ),
				'edit_item'     => __( 'Edit Contributor', 'lohkareet' ),
				'menu_name'     => __( 'Contributors', 'lohkareet' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-admin-users',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capability_type'     => 'post',
		)
	);

	register_post_type(
		'work',
		array(
			'labels'              => array(
				'name'          => __( 'Works', 'lohkareet' ),
				'singular_name' => __( 'Work', 'lohkareet' ),
				'add_new_item'  => __( 'Add New Work', 'lohkareet' ),
				'edit_item'     => __( 'Edit Work', 'lohkareet' ),
				'menu_name'     => __( 'Works', 'lohkareet' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-art',
			'menu_position'       => 23,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'capability_type'     => 'post',
		)
	);
}
add_action( 'init', 'lohkareet_register_work_post_types' );

/**
 * Contributor meta that is written separately for each language.
 *
 * The name is the post title of the original-language contributor. The
 * biography is the post content. Neither is copied here.
 *
 * @return string[]
 */
function lohkareet_translatable_contributor_meta_keys() {
	return array(
		'lohkareet_contributor_role',
	);
}

/**
 * Work meta that is the same in every language.
 *
 * @return string[]
 */
function lohkareet_shared_work_meta_keys() {
	return array(
		'lohkareet_contributor_ids',
	);
}

/**
 * Work meta written separately for each language.
 *
 * The description is the post content, not a meta field.
 *
 * @return string[]
 */
function lohkareet_translatable_work_meta_keys() {
	return array(
		'lohkareet_work_type',
		'lohkareet_work_title',
	);
}

/**
 * Registers contributor and work meta.
 */
function lohkareet_register_work_meta() {
	$groups = array(
		'contributor' => array(
			'lohkareet_contributor_role' => 'lohkareet_sanitize_plain_text',
		),
		'work'        => array(
			'lohkareet_work_type'        => 'lohkareet_sanitize_plain_text',
			'lohkareet_work_title'       => 'lohkareet_sanitize_plain_text',
			'lohkareet_contributor_ids'  => 'lohkareet_sanitize_contributor_id_list',
		),
	);

	foreach ( $groups as $post_type => $fields ) {
		foreach ( $fields as $key => $sanitize_callback ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => 'lohkareet_event_meta_auth',
					'sanitize_callback' => $sanitize_callback,
				)
			);
		}
	}
}
add_action( 'init', 'lohkareet_register_work_meta' );

/**
 * Splits a stored list of post IDs.
 *
 * @param mixed $value Stored or submitted value.
 * @return int[]
 */
function lohkareet_parse_id_list( $value ) {
	if ( is_array( $value ) ) {
		$parts = $value;
	} else {
		$parts = preg_split( '/[\s,]+/', (string) $value );
	}

	if ( ! is_array( $parts ) ) {
		return array();
	}

	$ids = array();

	foreach ( $parts as $part ) {
		$id = absint( $part );

		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Keeps contributor IDs that still point at a contributor.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_contributor_id_list( $value, ...$unused ) {
	unset( $unused );
	$ids = array();

	foreach ( lohkareet_parse_id_list( $value ) as $id ) {
		if ( 'contributor' === get_post_type( $id ) ) {
			$ids[] = $id;
		}
	}

	return implode( ',', $ids );
}

/**
 * Keeps work IDs that still point at a work.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_work_id_list( $value, ...$unused ) {
	unset( $unused );
	$ids = array();

	foreach ( lohkareet_parse_id_list( $value ) as $id ) {
		if ( 'work' === get_post_type( $id ) ) {
			$ids[] = $id;
		}
	}

	return implode( ',', $ids );
}

/**
 * Reads the works an editor checked on the event form.
 *
 * Returns null when this request did not include the works field, so another
 * save path does not clear the assignment.
 *
 * @return string|null Comma-separated IDs, or null to leave the stored list.
 */
function lohkareet_event_work_ids_from_request() {
	if ( ! isset( $_POST['lohkareet_works_present'] ) ) {
		return null;
	}

	$selected  = array();
	$positions = array();

	if ( isset( $_POST['lohkareet_work_selected'] ) && is_array( $_POST['lohkareet_work_selected'] ) ) {
		$selected = wp_unslash( $_POST['lohkareet_work_selected'] );
	}

	if ( isset( $_POST['lohkareet_work_position'] ) && is_array( $_POST['lohkareet_work_position'] ) ) {
		foreach ( wp_unslash( $_POST['lohkareet_work_position'] ) as $id => $position ) {
			$positions[ absint( $id ) ] = absint( $position );
		}
	}

	$ids = array();

	foreach ( $selected as $raw ) {
		$id = absint( $raw );

		if ( $id > 0 && 'work' === get_post_type( $id ) ) {
			$ids[] = $id;
		}
	}

	$ids = array_values( array_unique( $ids ) );

	usort(
		$ids,
		static function ( $a, $b ) use ( $positions ) {
			$pa = isset( $positions[ $a ] ) ? $positions[ $a ] : 0;
			$pb = isset( $positions[ $b ] ) ? $positions[ $b ] : 0;

			if ( $pa === $pb ) {
				return $a <=> $b;
			}

			if ( 0 === $pa ) {
				return 1;
			}

			if ( 0 === $pb ) {
				return -1;
			}

			return $pa <=> $pb;
		}
	);

	return implode( ',', $ids );
}

/**
 * Posts of one internal type, in the original language when Polylang is active.
 *
 * @param string $post_type contributor or work.
 * @return WP_Post[]
 */
function lohkareet_get_library_posts( $post_type ) {
	$args = array(
		'post_type'      => $post_type,
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( function_exists( 'pll_default_language' ) ) {
		$language = pll_default_language();

		if ( is_string( $language ) && '' !== $language ) {
			$args['lang'] = $language;
		}
	}

	$posts = get_posts( $args );

	return is_array( $posts ) ? $posts : array();
}

/**
 * Adds the contributor role field.
 */
function lohkareet_add_contributor_meta_box() {
	add_meta_box(
		'lohkareet_contributor_details',
		__( 'Contributor', 'lohkareet' ),
		'lohkareet_render_contributor_meta_box',
		'contributor',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_contributor', 'lohkareet_add_contributor_meta_box' );

/**
 * Prints the contributor form.
 *
 * @param WP_Post $post Contributor being edited.
 */
function lohkareet_render_contributor_meta_box( $post ) {
	wp_nonce_field( 'lohkareet_save_contributor_meta', 'lohkareet_contributor_meta_nonce' );
	$role = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_contributor_role' );
	?>
	<p class="description"><?php esc_html_e( 'The title is the public name. Keep that name the same in every language. Write a biography in the content editor only when there is sourced text.', 'lohkareet' ); ?></p>
	<p>
		<label for="lohkareet_contributor_role"><strong><?php esc_html_e( 'Role', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_contributor_role" name="lohkareet_contributor_role" value="<?php echo esc_attr( $role ); ?>">
	</p>
	<p class="description"><?php esc_html_e( 'The image is the featured image. It is shared with the other language version.', 'lohkareet' ); ?></p>
	<?php
}

/**
 * Saves the contributor role.
 *
 * @param int $post_id Contributor ID.
 */
function lohkareet_save_contributor_meta( $post_id ) {
	if ( ! isset( $_POST['lohkareet_contributor_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['lohkareet_contributor_meta_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'lohkareet_save_contributor_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$role = isset( $_POST['lohkareet_contributor_role'] ) ? lohkareet_sanitize_plain_text( wp_unslash( $_POST['lohkareet_contributor_role'] ) ) : '';

	if ( '' === $role ) {
		delete_post_meta( $post_id, 'lohkareet_contributor_role' );
		return;
	}

	update_post_meta( $post_id, 'lohkareet_contributor_role', $role );
}
add_action( 'save_post_contributor', 'lohkareet_save_contributor_meta' );

/**
 * Adds the work form.
 */
function lohkareet_add_work_meta_box() {
	add_meta_box(
		'lohkareet_work_details',
		__( 'Work', 'lohkareet' ),
		'lohkareet_render_work_meta_box',
		'work',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_work', 'lohkareet_add_work_meta_box' );

/**
 * Prints the work form.
 *
 * @param WP_Post $post Work being edited.
 */
function lohkareet_render_work_meta_box( $post ) {
	wp_nonce_field( 'lohkareet_save_work_meta', 'lohkareet_work_meta_nonce' );

	$type        = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_work_type' );
	$public_title = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_work_title' );
	$selected    = lohkareet_parse_id_list( lohkareet_get_event_meta_string( $post->ID, 'lohkareet_contributor_ids' ) );
	?>
	<p class="description"><?php esc_html_e( 'Write the work description in the content editor. That text is reused everywhere this work is assigned. Translate it on the other language version.', 'lohkareet' ); ?></p>
	<p>
		<label for="lohkareet_work_type"><strong><?php esc_html_e( 'Work type', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_work_type" name="lohkareet_work_type" value="<?php echo esc_attr( $type ); ?>">
	</p>
	<p>
		<label for="lohkareet_work_title"><strong><?php esc_html_e( 'Public title', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_work_title" name="lohkareet_work_title" value="<?php echo esc_attr( $public_title ); ?>">
		<span class="description"><?php esc_html_e( 'Optional. Leave this empty when the work is credited by its contributor’s name.', 'lohkareet' ); ?></span>
	</p>
	<fieldset>
		<legend><strong><?php esc_html_e( 'Contributors', 'lohkareet' ); ?></strong></legend>
		<input type="hidden" name="lohkareet_contributors_present" value="1">
		<?php
		$contributors = lohkareet_get_library_posts( 'contributor' );

		if ( ! $contributors ) {
			echo '<p class="description">' . esc_html__( 'No contributors have been added yet.', 'lohkareet' ) . '</p>';
		}

		foreach ( $contributors as $contributor ) {
			echo '<p><label><input type="checkbox" name="lohkareet_contributor_selected[]" value="' . esc_attr( (string) $contributor->ID ) . '" ' . checked( in_array( $contributor->ID, $selected, true ), true, false ) . '> ' . esc_html( get_the_title( $contributor ) ) . '</label></p>';
		}
		?>
	</fieldset>
	<?php
}

/**
 * Saves work details and copies the contributor list to translations.
 *
 * @param int $post_id Work ID.
 */
function lohkareet_save_work_meta( $post_id ) {
	if ( ! isset( $_POST['lohkareet_work_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['lohkareet_work_meta_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'lohkareet_save_work_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'lohkareet_work_type'  => 'lohkareet_sanitize_plain_text',
		'lohkareet_work_title' => 'lohkareet_sanitize_plain_text',
	);

	foreach ( $fields as $key => $sanitize_callback ) {
		$value = isset( $_POST[ $key ] ) ? call_user_func( $sanitize_callback, wp_unslash( $_POST[ $key ] ) ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		update_post_meta( $post_id, $key, $value );
	}

	if ( isset( $_POST['lohkareet_contributors_present'] ) ) {
		$raw = isset( $_POST['lohkareet_contributor_selected'] ) ? wp_unslash( $_POST['lohkareet_contributor_selected'] ) : '';
		$ids = lohkareet_sanitize_contributor_id_list( $raw );

		if ( '' === $ids ) {
			delete_post_meta( $post_id, 'lohkareet_contributor_ids' );
		} else {
			update_post_meta( $post_id, 'lohkareet_contributor_ids', $ids );
		}
	}

	lohkareet_sync_shared_event_meta( $post_id, lohkareet_shared_work_meta_keys() );
}
add_action( 'save_post_work', 'lohkareet_save_work_meta' );

/**
 * Prints the event’s work assignment list.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_render_event_works_field( $post_id ) {
	$selected = lohkareet_parse_id_list( lohkareet_get_event_meta_string( $post_id, 'lohkareet_work_ids' ) );
	$order    = array();

	foreach ( $selected as $index => $id ) {
		$order[ $id ] = $index + 1;
	}

	echo '<fieldset class="lohkareet-event-section">';
	echo '<legend><strong>' . esc_html__( 'Works', 'lohkareet' ) . '</strong> ' . lohkareet_event_scope_mark( 'lohkareet_work_ids' ) . '</legend>';
	echo '<p class="description">' . esc_html__( 'Tick a work to show it here. The number sets its order. The description is edited on the work, not on this event.', 'lohkareet' ) . '</p>';
	echo '<input type="hidden" name="lohkareet_works_present" value="1">';

	$works = lohkareet_get_library_posts( 'work' );

	if ( ! $works ) {
		echo '<p class="description">' . esc_html__( 'No works have been added yet.', 'lohkareet' ) . '</p>';
	}

	foreach ( $works as $work ) {
		$checked  = in_array( $work->ID, $selected, true );
		$position = $checked ? (string) $order[ $work->ID ] : '';
		echo '<p><label><input type="checkbox" name="lohkareet_work_selected[]" value="' . esc_attr( (string) $work->ID ) . '" ' . checked( $checked, true, false ) . '> ' . esc_html( get_the_title( $work ) ) . '</label> ';
		echo '<label>' . esc_html__( 'Order', 'lohkareet' ) . ' <input type="number" min="1" class="small-text" name="lohkareet_work_position[' . esc_attr( (string) $work->ID ) . ']" value="' . esc_attr( $position ) . '"></label></p>';
	}

	echo '</fieldset>';
}

/**
 * Post to read for a language, falling back to the stored post.
 *
 * @param int    $post_id Post ID.
 * @param string $which   current or source.
 */
function lohkareet_language_post_id( $post_id, $which ) {
	$post_id = (int) $post_id;

	if ( $post_id <= 0 || ! function_exists( 'pll_get_post' ) ) {
		return $post_id;
	}

	$language = '';

	if ( 'source' === $which && function_exists( 'pll_default_language' ) ) {
		$language = pll_default_language();
	} elseif ( 'current' === $which && function_exists( 'pll_current_language' ) ) {
		$language = pll_current_language();
	}

	if ( ! is_string( $language ) || '' === $language ) {
		return $post_id;
	}

	$translated = pll_get_post( $post_id, $language );

	return $translated ? (int) $translated : $post_id;
}

/**
 * Renders stored block content without inventing text around it.
 *
 * @param int $post_id Post that holds the editorial text.
 */
function lohkareet_render_stored_content( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$content = trim( $post->post_content );

	if ( '' === $content ) {
		return '';
	}

	$html = do_blocks( $content );

	return is_string( $html ) ? $html : '';
}

/**
 * Editorial text for this language, or the original when a translation is empty.
 *
 * @param int $post_id Original or translated post.
 */
function lohkareet_render_translated_content( $post_id ) {
	$current = lohkareet_language_post_id( $post_id, 'current' );
	$html    = lohkareet_render_stored_content( $current );

	if ( '' !== trim( wp_strip_all_tags( $html ) ) ) {
		return $html;
	}

	$source = lohkareet_language_post_id( $post_id, 'source' );

	if ( $source === $current ) {
		return '';
	}

	return lohkareet_render_stored_content( $source );
}

/**
 * One translated meta string, with the original as a fallback.
 *
 * @param int    $post_id Post ID stored on the event.
 * @param string $key     Meta key.
 */
function lohkareet_translated_meta_string( $post_id, $key ) {
	$current = lohkareet_language_post_id( $post_id, 'current' );
	$value   = lohkareet_get_event_meta_string( $current, $key );

	if ( '' !== $value ) {
		return $value;
	}

	$source = lohkareet_language_post_id( $post_id, 'source' );

	if ( $source === $current ) {
		return '';
	}

	return lohkareet_get_event_meta_string( $source, $key );
}

/**
 * Public names for a work, taken from the original-language contributors.
 *
 * @param int $work_id Work ID.
 * @return string[]
 */
function lohkareet_work_contributor_names( $work_id ) {
	$source = lohkareet_language_post_id( $work_id, 'source' );
	$ids    = lohkareet_parse_id_list( lohkareet_get_event_meta_string( $source, 'lohkareet_contributor_ids' ) );
	$names  = array();

	foreach ( $ids as $contributor_id ) {
		$name_id = lohkareet_language_post_id( $contributor_id, 'source' );
		$name    = get_the_title( $name_id );

		if ( is_string( $name ) && '' !== $name ) {
			$names[] = $name;
		}
	}

	return $names;
}
