<?php
/**
 * Event metadata and the event editing form.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta keys and the callback that sanitizes each one.
 *
 * @return array<string, string>
 */
function lohkareet_event_meta_fields() {
	return array(
		'lohkareet_date_year'             => 'lohkareet_sanitize_date_year',
		'lohkareet_date_month'            => 'lohkareet_sanitize_date_month',
		'lohkareet_date_day'              => 'lohkareet_sanitize_date_day',
		'lohkareet_start_time'            => 'lohkareet_sanitize_time',
		'lohkareet_end_time'              => 'lohkareet_sanitize_time',
		'lohkareet_municipality'          => 'lohkareet_sanitize_municipality',
		'lohkareet_venue'                 => 'lohkareet_sanitize_plain_text',
		'lohkareet_address'               => 'lohkareet_sanitize_plain_text',
		'lohkareet_location_description'  => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_arrival_point'         => 'lohkareet_sanitize_plain_text',
		'lohkareet_arrival_description'   => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_map_latitude'          => 'lohkareet_sanitize_latitude',
		'lohkareet_map_longitude'         => 'lohkareet_sanitize_longitude',
		'lohkareet_map_point'             => 'lohkareet_sanitize_map_point',
		'lohkareet_map_url'               => 'lohkareet_sanitize_https_url',
		'lohkareet_zoneatlas_url'         => 'lohkareet_sanitize_https_url',
		'lohkareet_artist_name'           => 'lohkareet_sanitize_plain_text',
		'lohkareet_artist_description'    => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_artist_status'         => 'lohkareet_sanitize_artist_status',
		'lohkareet_artist_image'          => 'lohkareet_sanitize_image_id',
		'lohkareet_social_image'          => 'lohkareet_sanitize_image_id',
		'lohkareet_social_description'    => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_work_ids'              => 'lohkareet_sanitize_work_id_list',
	);
}

/**
 * Event meta that is the same in every language.
 *
 * Dates, place identifiers, and artist status are not translated. Each
 * language version stores its own copy so queries stay on that post.
 * Saving the event form copies these keys to linked translations.
 *
 * @return string[]
 */
function lohkareet_shared_event_meta_keys() {
	return array(
		'lohkareet_date_year',
		'lohkareet_date_month',
		'lohkareet_date_day',
		'lohkareet_start_time',
		'lohkareet_end_time',
		'lohkareet_municipality',
		'lohkareet_venue',
		'lohkareet_address',
		'lohkareet_arrival_point',
		'lohkareet_map_latitude',
		'lohkareet_map_longitude',
		'lohkareet_map_point',
		'lohkareet_map_url',
		'lohkareet_zoneatlas_url',
		'lohkareet_artist_name',
		'lohkareet_artist_status',
		'lohkareet_artist_image',
		'lohkareet_social_image',
		'lohkareet_work_ids',
	);
}

/**
 * Event meta written separately for each language.
 *
 * The post title and block-editor content are translated as normal post
 * fields. They are not in this list.
 *
 * @return string[]
 */
function lohkareet_translatable_event_meta_keys() {
	return array(
		'lohkareet_location_description',
		'lohkareet_arrival_description',
		'lohkareet_artist_description',
		'lohkareet_social_description',
	);
}

/**
 * Registers event meta for the block editor and the REST API.
 */
function lohkareet_register_event_meta() {
	foreach ( lohkareet_event_meta_fields() as $key => $sanitize_callback ) {
		register_post_meta(
			'event',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => 'lohkareet_event_meta_auth',
				'sanitize_callback' => $sanitize_callback,
				// WordPress 6.4 copies these values onto revisions and restores them.
				// The theme requires WordPress 6.6.
				'revisions_enabled' => true,
			)
		);
	}
}
add_action( 'init', 'lohkareet_register_event_meta' );

/**
 * Marker stored on event revisions saved after meta revisions were enabled.
 *
 * WordPress deletes every revisioned field on restore, then copies back only
 * the keys present on the revision. A revision saved before this support has
 * none of those keys, so restore would erase the current event. The marker
 * is written for every new snapshot, including one where a field was cleared.
 *
 * @return string
 */
function lohkareet_event_meta_revision_marker() {
	return '_lohkareet_event_meta_revision';
}

/**
 * Records that an event revision contains a meta snapshot.
 *
 * Runs after WordPress has copied the current meta onto the revision.
 *
 * @param int $revision_id Revision ID.
 * @param int $post_id     Parent event ID.
 */
function lohkareet_mark_event_meta_revision( $revision_id, $post_id ) {
	if ( 'event' !== get_post_type( $post_id ) ) {
		return;
	}

	update_metadata( 'post', (int) $revision_id, lohkareet_event_meta_revision_marker(), '1' );
}
add_action( '_wp_put_post_revision', 'lohkareet_mark_event_meta_revision', 11, 2 );

/**
 * Remembers whether the revision being restored predates meta snapshots.
 *
 * @param bool|null $skip Null reads the flag. True or false sets it.
 * @return bool
 */
function lohkareet_skip_unsnapshotted_event_meta_restore( $skip = null ) {
	static $active = false;

	if ( is_bool( $skip ) ) {
		$active = $skip;
	}

	return $active;
}

/**
 * Keeps current event meta when the revision has no snapshot marker.
 *
 * Post content still restores. A snapshot with an intentionally empty field
 * keeps the marker, so that field is cleared.
 *
 * @param int $post_id     Event being restored.
 * @param int $revision_id Revision being restored.
 */
function lohkareet_guard_event_meta_revision_restore( $post_id, $revision_id ) {
	$skip = 'event' === get_post_type( $post_id )
		&& ! metadata_exists( 'post', (int) $revision_id, lohkareet_event_meta_revision_marker() );

	lohkareet_skip_unsnapshotted_event_meta_restore( $skip );
}
add_action( 'wp_restore_post_revision', 'lohkareet_guard_event_meta_revision_restore', 9, 2 );

/**
 * Clears the restore guard after WordPress has restored revision meta.
 */
function lohkareet_finish_event_meta_revision_restore() {
	lohkareet_skip_unsnapshotted_event_meta_restore( false );
}
add_action( 'wp_restore_post_revision', 'lohkareet_finish_event_meta_revision_restore', 11 );

/**
 * Drops event meta from an old revision restore.
 *
 * @param string[] $keys      Revisioned meta keys.
 * @param string   $post_type Post type being restored.
 * @return string[]
 */
function lohkareet_filter_event_revision_meta_keys( $keys, $post_type ) {
	if ( 'event' !== $post_type || ! lohkareet_skip_unsnapshotted_event_meta_restore() ) {
		return $keys;
	}

	return array_values( array_diff( $keys, array_keys( lohkareet_event_meta_fields() ) ) );
}
add_filter( 'wp_post_revision_meta_keys', 'lohkareet_filter_event_revision_meta_keys', 10, 2 );

/**
 * Limits event-meta changes to people who can edit the event.
 *
 * @param bool   $allowed   Whether the user can already access the meta.
 * @param string $meta_key  Meta key.
 * @param int    $object_id Post ID.
 */
function lohkareet_event_meta_auth( $allowed, $meta_key, $object_id, ...$unused ) {
	unset( $unused );
	if ( ! is_string( $meta_key ) || 0 !== strpos( $meta_key, 'lohkareet_' ) ) {
		return (bool) $allowed;
	}

	return current_user_can( 'edit_post', (int) $object_id );
}

/**
 * Prints the language-scope mark for one event field.
 *
 * @param string $key Meta key.
 * @return string
 */
function lohkareet_event_scope_mark( $key ) {
	$shared = in_array( $key, lohkareet_shared_event_meta_keys(), true );
	$label  = $shared ? __( 'FI + EN', 'lohkareet' ) : __( 'This language only', 'lohkareet' );
	$scope  = $shared ? 'shared' : 'language';

	return '<span class="lohkareet-event-scope is-' . esc_attr( $scope ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Prints a field label with its language-scope mark.
 *
 * @param string $id     Input id.
 * @param string $label  Visible label.
 * @param string $key    Meta key.
 * @param bool   $strong Whether the label text is strong.
 */
function lohkareet_event_field_label( $id, $label, $key, $strong = true ) {
	echo '<label for="' . esc_attr( $id ) . '">';

	if ( $strong ) {
		echo '<strong>' . esc_html( $label ) . '</strong>';
	} else {
		echo esc_html( $label );
	}

	echo ' ' . lohkareet_event_scope_mark( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</label>';
}

/**
 * Adds the event details form below the content editor.
 */
function lohkareet_add_event_meta_box() {
	add_meta_box(
		'lohkareet_event_details',
		__( 'Lohkareet – event details', 'lohkareet' ),
		'lohkareet_render_event_meta_box',
		'event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_event', 'lohkareet_add_event_meta_box' );

/**
 * Prints the event details form.
 *
 * @param WP_Post $post Event being edited.
 */
function lohkareet_render_event_meta_box( $post ) {
	wp_nonce_field( 'lohkareet_save_event_meta', 'lohkareet_event_meta_nonce' );

	$year        = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_date_year' );
	$month       = lohkareet_get_event_int( $post->ID, 'lohkareet_date_month' );
	$day         = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_date_day' );
	$start       = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_start_time' );
	$end         = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_end_time' );
	$municipality = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_municipality' );
	$venue       = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_venue' );
	$address     = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_address' );
	$location    = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_location_description' );
	$arrival_point = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_arrival_point' );
	$arrival     = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_arrival_description' );
	$latitude    = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_map_latitude' );
	$longitude   = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_map_longitude' );
	$map_point   = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_map_point' );
	$map_url     = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_map_url' );
	$zoneatlas   = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_zoneatlas_url' );
	$artist      = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_artist_name' );
	$artist_text = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_artist_description' );
	$status      = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_artist_status' );
	$artist_image = lohkareet_get_event_meta_string( $post->ID, 'lohkareet_artist_image' );

	if ( '' === $status ) {
		$status = 'unannounced';
	}

	echo '<p class="description">' . esc_html__( 'Leave a field empty when that fact is still unknown. Do not type a “will be announced later” sentence into a field.', 'lohkareet' ) . '</p>';
	echo '<p class="description">' . esc_html__( 'Fields marked FI + EN are copied to the translation. Fields marked for this language stay on this version.', 'lohkareet' ) . '</p>';
	?>
	<fieldset class="lohkareet-event-section">
		<legend><strong><?php esc_html_e( 'Date and time', 'lohkareet' ); ?></strong></legend>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_date_day', __( 'Day', 'lohkareet' ), 'lohkareet_date_day', false ); ?><br>
			<input type="number" id="lohkareet_date_day" name="lohkareet_date_day" min="1" max="31" value="<?php echo esc_attr( $day ); ?>">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_date_month', __( 'Month', 'lohkareet' ), 'lohkareet_date_month', false ); ?><br>
			<select id="lohkareet_date_month" name="lohkareet_date_month">
				<option value=""><?php esc_html_e( 'Unknown', 'lohkareet' ); ?></option>
				<?php for ( $month_number = 1; $month_number <= 12; $month_number++ ) : ?>
					<option value="<?php echo esc_attr( (string) $month_number ); ?>" <?php selected( $month, $month_number ); ?>>
						<?php echo esc_html( lohkareet_month_name( $month_number ) ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_date_year', __( 'Year', 'lohkareet' ), 'lohkareet_date_year', false ); ?><br>
			<input type="number" id="lohkareet_date_year" name="lohkareet_date_year" min="1970" max="2100" value="<?php echo esc_attr( $year ); ?>">
		</p>
		<p class="description"><?php esc_html_e( 'A day can be saved without a year. A month is required when the day is known.', 'lohkareet' ); ?></p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_start_time', __( 'Start time', 'lohkareet' ), 'lohkareet_start_time', false ); ?><br>
			<input type="time" id="lohkareet_start_time" name="lohkareet_start_time" value="<?php echo esc_attr( $start ); ?>">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_end_time', __( 'End time', 'lohkareet' ), 'lohkareet_end_time', false ); ?><br>
			<input type="time" id="lohkareet_end_time" name="lohkareet_end_time" value="<?php echo esc_attr( $end ); ?>">
		</p>
	</fieldset>

	<fieldset class="lohkareet-event-section">
		<legend><strong><?php esc_html_e( 'Event place', 'lohkareet' ); ?></strong></legend>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_municipality', __( 'Municipality', 'lohkareet' ), 'lohkareet_municipality' ); ?><br>
			<select id="lohkareet_municipality" name="lohkareet_municipality">
				<option value=""><?php esc_html_e( 'Unknown', 'lohkareet' ); ?></option>
				<?php foreach ( lohkareet_get_municipalities() as $name ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $municipality, $name ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_venue', __( 'Venue', 'lohkareet' ), 'lohkareet_venue' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_venue" name="lohkareet_venue" value="<?php echo esc_attr( $venue ); ?>">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_address', __( 'Street address', 'lohkareet' ), 'lohkareet_address' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_address" name="lohkareet_address" value="<?php echo esc_attr( $address ); ?>">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_location_description', __( 'Location description', 'lohkareet' ), 'lohkareet_location_description' ); ?><br>
			<textarea class="widefat" rows="6" id="lohkareet_location_description" name="lohkareet_location_description"><?php echo esc_textarea( $location ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Editorial text for the place. This is not the arrival instructions.', 'lohkareet' ); ?></span>
		</p>
	</fieldset>

	<fieldset class="lohkareet-event-section">
		<legend><strong><?php esc_html_e( 'Arrival', 'lohkareet' ); ?></strong></legend>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_arrival_point', __( 'Arrival point', 'lohkareet' ), 'lohkareet_arrival_point' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_arrival_point" name="lohkareet_arrival_point" value="<?php echo esc_attr( $arrival_point ); ?>">
			<span class="description"><?php esc_html_e( 'The named point a visitor navigates to, when that is not the event location itself.', 'lohkareet' ); ?></span>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_arrival_description', __( 'Arrival instructions', 'lohkareet' ), 'lohkareet_arrival_description' ); ?><br>
			<textarea class="widefat" rows="4" id="lohkareet_arrival_description" name="lohkareet_arrival_description"><?php echo esc_textarea( $arrival ); ?></textarea>
		</p>
	</fieldset>

	<fieldset class="lohkareet-event-section">
		<legend><strong><?php esc_html_e( 'Maps', 'lohkareet' ); ?></strong></legend>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_map_latitude', __( 'Latitude', 'lohkareet' ), 'lohkareet_map_latitude' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_map_latitude" name="lohkareet_map_latitude" inputmode="decimal" value="<?php echo esc_attr( $latitude ); ?>" placeholder="61.140131">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_map_longitude', __( 'Longitude', 'lohkareet' ), 'lohkareet_map_longitude' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_map_longitude" name="lohkareet_map_longitude" inputmode="decimal" value="<?php echo esc_attr( $longitude ); ?>" placeholder="23.132504">
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_map_point', __( 'Mapped point', 'lohkareet' ), 'lohkareet_map_point' ); ?><br>
			<select id="lohkareet_map_point" name="lohkareet_map_point">
				<option value=""><?php esc_html_e( 'Unknown', 'lohkareet' ); ?></option>
				<option value="event" <?php selected( $map_point, 'event' ); ?>><?php esc_html_e( 'Event location', 'lohkareet' ); ?></option>
				<option value="arrival" <?php selected( $map_point, 'arrival' ); ?>><?php esc_html_e( 'Arrival point', 'lohkareet' ); ?></option>
			</select>
			<span class="description"><?php esc_html_e( 'Decimal degrees for one verified point. Say whether that point is the event location or only the arrival point. Leave the coordinates empty when the point is not confirmed.', 'lohkareet' ); ?></span>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_map_url', __( 'Google Maps URL', 'lohkareet' ), 'lohkareet_map_url' ); ?><br>
			<input type="url" class="widefat" id="lohkareet_map_url" name="lohkareet_map_url" value="<?php echo esc_attr( $map_url ); ?>" placeholder="https://">
			<span class="description"><?php esc_html_e( 'Optional https link for the Google Maps button. Leave this empty to build the link from the coordinates. This is not the embedded map.', 'lohkareet' ); ?></span>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_zoneatlas_url', __( 'Zoneatlas URL', 'lohkareet' ), 'lohkareet_zoneatlas_url' ); ?><br>
			<input type="url" class="widefat" id="lohkareet_zoneatlas_url" name="lohkareet_zoneatlas_url" value="<?php echo esc_attr( $zoneatlas ); ?>" placeholder="https://">
			<span class="description"><?php esc_html_e( 'Https address of this event’s Zoneatlas map. Leave this empty to show the series map.', 'lohkareet' ); ?></span>
		</p>
	</fieldset>

	<fieldset class="lohkareet-event-section">
		<legend><strong><?php esc_html_e( 'Performing artist', 'lohkareet' ); ?></strong></legend>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_artist_name', __( 'Performing artist', 'lohkareet' ), 'lohkareet_artist_name' ); ?><br>
			<input type="text" class="widefat" id="lohkareet_artist_name" name="lohkareet_artist_name" value="<?php echo esc_attr( $artist ); ?>">
			<span class="description"><?php esc_html_e( 'This is the main performing artist. Recurring works are assigned below and are not additional artists.', 'lohkareet' ); ?></span>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_artist_status', __( 'Artist status', 'lohkareet' ), 'lohkareet_artist_status' ); ?><br>
			<select id="lohkareet_artist_status" name="lohkareet_artist_status">
				<option value="confirmed" <?php selected( $status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'lohkareet' ); ?></option>
				<option value="tentative" <?php selected( $status, 'tentative' ); ?>><?php esc_html_e( 'Tentative', 'lohkareet' ); ?></option>
				<option value="unannounced" <?php selected( $status, 'unannounced' ); ?>><?php esc_html_e( 'Unannounced', 'lohkareet' ); ?></option>
			</select>
			<span class="description"><?php esc_html_e( 'The name is shown only when the status is confirmed or tentative.', 'lohkareet' ); ?></span>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_artist_description', __( 'Artist description', 'lohkareet' ), 'lohkareet_artist_description' ); ?><br>
			<textarea class="widefat" rows="4" id="lohkareet_artist_description" name="lohkareet_artist_description"><?php echo esc_textarea( $artist_text ); ?></textarea>
		</p>
		<p>
			<?php lohkareet_event_field_label( 'lohkareet_artist_image', __( 'Artist image', 'lohkareet' ), 'lohkareet_artist_image' ); ?><br>
			<input type="hidden" id="lohkareet_artist_image" name="lohkareet_artist_image" value="<?php echo esc_attr( $artist_image ); ?>">
			<span id="lohkareet-artist-image-preview">
				<?php
				if ( '' !== $artist_image ) {
					echo wp_get_attachment_image( (int) $artist_image, 'medium' );
				}
				?>
			</span><br>
			<button type="button" class="button" id="lohkareet-artist-image-button"><?php esc_html_e( 'Select image', 'lohkareet' ); ?></button>
			<button type="button" class="button-link" id="lohkareet-artist-image-remove"><?php esc_html_e( 'Remove image', 'lohkareet' ); ?></button>
			<span class="description"><?php esc_html_e( 'Separate from the event’s featured image.', 'lohkareet' ); ?></span>
		</p>
	</fieldset>
	<?php
	lohkareet_render_event_works_field( $post->ID );
	lohkareet_render_social_fields( $post->ID, 'event' );
	?>
	<p class="description"><?php esc_html_e( 'Write the event description in the content editor. Set the event image as the featured image.', 'lohkareet' ); ?></p>
	<?php
}

/**
 * Saves event details from the meta box.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_save_event_meta( $post_id ) {
	if ( ! isset( $_POST['lohkareet_event_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['lohkareet_event_meta_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'lohkareet_save_event_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( isset( $_POST['post_ID'] ) && is_scalar( $_POST['post_ID'] ) ) {
		$posted_id = absint( wp_unslash( $_POST['post_ID'] ) );

		if ( $posted_id !== (int) $post_id ) {
			return;
		}
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$values = array();

	foreach ( lohkareet_event_meta_fields() as $key => $sanitize_callback ) {
		if ( 'lohkareet_work_ids' === $key ) {
			continue;
		}

		$raw            = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$values[ $key ] = call_user_func( $sanitize_callback, $raw );
	}

	$work_ids = lohkareet_event_work_ids_from_request();

	if ( null !== $work_ids ) {
		$values['lohkareet_work_ids'] = lohkareet_sanitize_work_id_list( $work_ids );
	}

	if ( '' !== $values['lohkareet_date_day'] && '' === $values['lohkareet_date_month'] ) {
		$values['lohkareet_date_day'] = '';
	}

	$year  = absint( $values['lohkareet_date_year'] );
	$month = absint( $values['lohkareet_date_month'] );
	$day   = absint( $values['lohkareet_date_day'] );

	if ( $month && $day ) {
		$check_year = $year ? $year : 2024;

		if ( ! checkdate( $month, $day, $check_year ) ) {
			$values['lohkareet_date_day'] = '';
		}
	}

	foreach ( $values as $key => $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		update_post_meta( $post_id, $key, $value );
	}

	lohkareet_sync_shared_event_meta( $post_id );
}
add_action( 'save_post_event', 'lohkareet_save_event_meta' );

/**
 * Month name in the site language.
 *
 * @param int $month Month number, 1–12.
 */
function lohkareet_month_name( $month ) {
	global $wp_locale;

	if ( $wp_locale instanceof WP_Locale ) {
		return $wp_locale->get_month( $month );
	}

	return (string) $month;
}

/**
 * Reduces mixed input to a trimmed string.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_scalar( $value, ...$unused ) {
	unset( $unused );
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}

	return trim( (string) $value );
}

/**
 * Sanitizes a year. An empty string means the year is unknown.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_date_year( $value, ...$unused ) {
	unset( $unused );
	$year = absint( $value );

	if ( $year < 1970 || $year > 2100 ) {
		return '';
	}

	return (string) $year;
}

/**
 * Sanitizes a month number.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_date_month( $value, ...$unused ) {
	unset( $unused );
	$month = absint( $value );

	if ( $month < 1 || $month > 12 ) {
		return '';
	}

	return (string) $month;
}

/**
 * Sanitizes a day number.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_date_day( $value, ...$unused ) {
	unset( $unused );
	$day = absint( $value );

	if ( $day < 1 || $day > 31 ) {
		return '';
	}

	return (string) $day;
}

/**
 * Sanitizes a clock time as HH:MM.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_time( $value, ...$unused ) {
	unset( $unused );
	$value = lohkareet_sanitize_scalar( $value );

	if ( ! preg_match( '/^(\d{2}):(\d{2})/', $value, $matches ) ) {
		return '';
	}

	$hour   = (int) $matches[1];
	$minute = (int) $matches[2];

	if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 ) {
		return '';
	}

	return sprintf( '%02d:%02d', $hour, $minute );
}

/**
 * Sanitizes a municipality against the known list.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_municipality( $value, ...$unused ) {
	unset( $unused );
	$value = sanitize_text_field( lohkareet_sanitize_scalar( $value ) );

	if ( in_array( $value, lohkareet_get_municipalities(), true ) ) {
		return $value;
	}

	return '';
}

/**
 * Sanitizes a single-line text field.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_plain_text( $value, ...$unused ) {
	unset( $unused );
	return sanitize_text_field( lohkareet_sanitize_scalar( $value ) );
}

/**
 * Sanitizes a plain-text description.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_plain_textarea( $value, ...$unused ) {
	unset( $unused );
	return sanitize_textarea_field( lohkareet_sanitize_scalar( $value ) );
}

/**
 * Sanitizes a decimal coordinate inside an inclusive range.
 *
 * @param mixed $value Raw value.
 * @param float $min   Lowest accepted number.
 * @param float $max   Highest accepted number.
 */
function lohkareet_sanitize_decimal_coordinate( $value, $min, $max ) {
	$value = str_replace( ',', '.', lohkareet_sanitize_scalar( $value ) );
	$value = trim( $value );

	if ( ! is_numeric( $value ) ) {
		return '';
	}

	$number = (float) $value;

	if ( $number < $min || $number > $max ) {
		return '';
	}

	$formatted = rtrim( rtrim( sprintf( '%.7F', $number ), '0' ), '.' );

	return '-0' === $formatted ? '0' : $formatted;
}

/**
 * Sanitizes a latitude.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_latitude( $value, ...$unused ) {
	unset( $unused );
	return lohkareet_sanitize_decimal_coordinate( $value, -90, 90 );
}

/**
 * Sanitizes a longitude.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_longitude( $value, ...$unused ) {
	unset( $unused );
	return lohkareet_sanitize_decimal_coordinate( $value, -180, 180 );
}

/**
 * Sanitizes whether the mapped coordinates are the event or the arrival point.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_map_point( $value, ...$unused ) {
	unset( $unused );
	$value = sanitize_key( lohkareet_sanitize_scalar( $value ) );

	if ( in_array( $value, array( 'event', 'arrival' ), true ) ) {
		return $value;
	}

	return '';
}

/**
 * Sanitizes an embed URL. Only https URLs are kept.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_https_url( $value, ...$unused ) {
	unset( $unused );
	$url = esc_url_raw( lohkareet_sanitize_scalar( $value ) );

	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );

	if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) || 'https' !== $parts['scheme'] ) {
		return '';
	}

	return $url;
}

/**
 * Sanitizes an attachment ID. Only an image attachment is kept.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_image_id( $value, ...$unused ) {
	unset( $unused );
	$attachment_id = absint( $value );

	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}

	return (string) $attachment_id;
}

/**
 * Sanitizes the artist status.
 *
 * @param mixed $value Raw value.
 */
function lohkareet_sanitize_artist_status( $value, ...$unused ) {
	unset( $unused );
	$value   = sanitize_key( lohkareet_sanitize_scalar( $value ) );
	$allowed = array( 'confirmed', 'tentative', 'unannounced' );

	if ( in_array( $value, $allowed, true ) ) {
		return $value;
	}

	return 'unannounced';
}

/**
 * Loads the media picker used for the performing artist’s image.
 *
 * @param string $hook Current admin page.
 */
function lohkareet_enqueue_event_editor( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || ! in_array( $screen->post_type, array( 'event', 'page' ), true ) ) {
		return;
	}

	$is_event = 'event' === $screen->post_type;
	$version  = wp_get_theme()->get( 'Version' );
	$deps     = array( 'media-editor' );

	if ( $is_event ) {
		$deps = array( 'media-editor', 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-commands' );
		wp_enqueue_style(
			'lohkareet-event-editor',
			get_theme_file_uri( 'assets/css/event-editor.css' ),
			array(),
			$version
		);
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'lohkareet-event-editor',
		get_theme_file_uri( 'assets/js/event-editor.js' ),
		$deps,
		$version,
		true
	);

	if ( ! $is_event ) {
		return;
	}

	wp_add_inline_script(
		'lohkareet-event-editor',
		'window.lohkareetEventEditor = ' . wp_json_encode(
			array(
				'postType'  => 'event',
				'metaBoxId' => 'lohkareet_event_details',
			)
		) . ';',
		'before'
	);
	lohkareet_inject_event_editor_translations();
}

/**
 * Gives the event editor shortcut the same Finnish translations as PHP.
 */
function lohkareet_inject_event_editor_translations() {
	$msgids = array(
		'Go to event details',
		'Lohkareet – event details',
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
		'lohkareet-event-editor',
		'wp.i18n.setLocaleData( ' . wp_json_encode( $data ) . ', "lohkareet" );',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'lohkareet_enqueue_event_editor' );
