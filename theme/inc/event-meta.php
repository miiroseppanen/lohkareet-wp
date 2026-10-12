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
			)
		);
	}
}
add_action( 'init', 'lohkareet_register_event_meta' );

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
 * Adds the event details form below the content editor.
 */
function lohkareet_add_event_meta_box() {
	add_meta_box(
		'lohkareet_event_details',
		__( 'Event details', 'lohkareet' ),
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
	echo '<p class="description">' . esc_html__( 'Date, time, municipality, venue, address, arrival point, coordinates, map point, Google Maps URL, Zoneatlas URL, performing artist, artist status, artist image, social image, and assigned works are shared. Translate the location description, the arrival instructions, the artist description, and the social description on each version. Work descriptions are translated on the work.', 'lohkareet' ) . '</p>';
	?>
	<fieldset>
		<legend><strong><?php esc_html_e( 'Date', 'lohkareet' ); ?></strong></legend>
		<p>
			<label for="lohkareet_date_day"><?php esc_html_e( 'Day', 'lohkareet' ); ?></label><br>
			<input type="number" id="lohkareet_date_day" name="lohkareet_date_day" min="1" max="31" value="<?php echo esc_attr( $day ); ?>">
		</p>
		<p>
			<label for="lohkareet_date_month"><?php esc_html_e( 'Month', 'lohkareet' ); ?></label><br>
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
			<label for="lohkareet_date_year"><?php esc_html_e( 'Year', 'lohkareet' ); ?></label><br>
			<input type="number" id="lohkareet_date_year" name="lohkareet_date_year" min="1970" max="2100" value="<?php echo esc_attr( $year ); ?>">
		</p>
		<p class="description"><?php esc_html_e( 'A day can be saved without a year. A month is required when the day is known.', 'lohkareet' ); ?></p>
	</fieldset>

	<fieldset>
		<legend><strong><?php esc_html_e( 'Time', 'lohkareet' ); ?></strong></legend>
		<p>
			<label for="lohkareet_start_time"><?php esc_html_e( 'Start time', 'lohkareet' ); ?></label><br>
			<input type="time" id="lohkareet_start_time" name="lohkareet_start_time" value="<?php echo esc_attr( $start ); ?>">
		</p>
		<p>
			<label for="lohkareet_end_time"><?php esc_html_e( 'End time', 'lohkareet' ); ?></label><br>
			<input type="time" id="lohkareet_end_time" name="lohkareet_end_time" value="<?php echo esc_attr( $end ); ?>">
		</p>
	</fieldset>

	<p>
		<label for="lohkareet_municipality"><strong><?php esc_html_e( 'Municipality', 'lohkareet' ); ?></strong></label><br>
		<select id="lohkareet_municipality" name="lohkareet_municipality">
			<option value=""><?php esc_html_e( 'Unknown', 'lohkareet' ); ?></option>
			<?php foreach ( lohkareet_get_municipalities() as $name ) : ?>
				<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $municipality, $name ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="lohkareet_venue"><strong><?php esc_html_e( 'Venue', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_venue" name="lohkareet_venue" value="<?php echo esc_attr( $venue ); ?>">
	</p>
	<p>
		<label for="lohkareet_address"><strong><?php esc_html_e( 'Street address', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_address" name="lohkareet_address" value="<?php echo esc_attr( $address ); ?>">
	</p>
	<p>
		<label for="lohkareet_location_description"><strong><?php esc_html_e( 'Location description', 'lohkareet' ); ?></strong></label><br>
		<textarea class="widefat" rows="6" id="lohkareet_location_description" name="lohkareet_location_description"><?php echo esc_textarea( $location ); ?></textarea>
		<span class="description"><?php esc_html_e( 'Editorial text for the place. This is not the arrival instructions.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_arrival_point"><strong><?php esc_html_e( 'Arrival point', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_arrival_point" name="lohkareet_arrival_point" value="<?php echo esc_attr( $arrival_point ); ?>">
		<span class="description"><?php esc_html_e( 'The named point a visitor navigates to, when that is not the event location itself.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_arrival_description"><strong><?php esc_html_e( 'Arrival instructions', 'lohkareet' ); ?></strong></label><br>
		<textarea class="widefat" rows="4" id="lohkareet_arrival_description" name="lohkareet_arrival_description"><?php echo esc_textarea( $arrival ); ?></textarea>
	</p>
	<p>
		<label for="lohkareet_map_latitude"><strong><?php esc_html_e( 'Latitude', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_map_latitude" name="lohkareet_map_latitude" inputmode="decimal" value="<?php echo esc_attr( $latitude ); ?>" placeholder="61.140131">
	</p>
	<p>
		<label for="lohkareet_map_longitude"><strong><?php esc_html_e( 'Longitude', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_map_longitude" name="lohkareet_map_longitude" inputmode="decimal" value="<?php echo esc_attr( $longitude ); ?>" placeholder="23.132504">
	</p>
	<p>
		<label for="lohkareet_map_point"><strong><?php esc_html_e( 'Mapped point', 'lohkareet' ); ?></strong></label><br>
		<select id="lohkareet_map_point" name="lohkareet_map_point">
			<option value=""><?php esc_html_e( 'Unknown', 'lohkareet' ); ?></option>
			<option value="event" <?php selected( $map_point, 'event' ); ?>><?php esc_html_e( 'Event location', 'lohkareet' ); ?></option>
			<option value="arrival" <?php selected( $map_point, 'arrival' ); ?>><?php esc_html_e( 'Arrival point', 'lohkareet' ); ?></option>
		</select>
		<span class="description"><?php esc_html_e( 'Decimal degrees for one verified point. Say whether that point is the event location or only the arrival point. Leave the coordinates empty when the point is not confirmed.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_map_url"><strong><?php esc_html_e( 'Google Maps URL', 'lohkareet' ); ?></strong></label><br>
		<input type="url" class="widefat" id="lohkareet_map_url" name="lohkareet_map_url" value="<?php echo esc_attr( $map_url ); ?>" placeholder="https://">
		<span class="description"><?php esc_html_e( 'Optional https link for the Google Maps button. Leave this empty to build the link from the coordinates. This is not the embedded map.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_zoneatlas_url"><strong><?php esc_html_e( 'Zoneatlas URL', 'lohkareet' ); ?></strong></label><br>
		<input type="url" class="widefat" id="lohkareet_zoneatlas_url" name="lohkareet_zoneatlas_url" value="<?php echo esc_attr( $zoneatlas ); ?>" placeholder="https://">
		<span class="description"><?php esc_html_e( 'Https address of this event’s Zoneatlas map. Leave this empty to show the series map.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_artist_name"><strong><?php esc_html_e( 'Performing artist', 'lohkareet' ); ?></strong></label><br>
		<input type="text" class="widefat" id="lohkareet_artist_name" name="lohkareet_artist_name" value="<?php echo esc_attr( $artist ); ?>">
		<span class="description"><?php esc_html_e( 'This is the main performing artist. Recurring works are assigned below and are not additional artists.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_artist_status"><strong><?php esc_html_e( 'Artist status', 'lohkareet' ); ?></strong></label><br>
		<select id="lohkareet_artist_status" name="lohkareet_artist_status">
			<option value="confirmed" <?php selected( $status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'lohkareet' ); ?></option>
			<option value="tentative" <?php selected( $status, 'tentative' ); ?>><?php esc_html_e( 'Tentative', 'lohkareet' ); ?></option>
			<option value="unannounced" <?php selected( $status, 'unannounced' ); ?>><?php esc_html_e( 'Unannounced', 'lohkareet' ); ?></option>
		</select>
		<span class="description"><?php esc_html_e( 'The name is shown only when the status is confirmed or tentative.', 'lohkareet' ); ?></span>
	</p>
	<p>
		<label for="lohkareet_artist_description"><strong><?php esc_html_e( 'Artist description', 'lohkareet' ); ?></strong></label><br>
		<textarea class="widefat" rows="4" id="lohkareet_artist_description" name="lohkareet_artist_description"><?php echo esc_textarea( $artist_text ); ?></textarea>
	</p>
	<p>
		<label for="lohkareet_artist_image"><strong><?php esc_html_e( 'Artist image', 'lohkareet' ); ?></strong></label><br>
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
	<?php
	lohkareet_render_social_fields( $post->ID, 'event' );
	lohkareet_render_event_works_field( $post->ID );
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

	wp_enqueue_media();
	wp_enqueue_script(
		'lohkareet-event-editor',
		get_theme_file_uri( 'assets/js/event-editor.js' ),
		array( 'media-editor' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'lohkareet_enqueue_event_editor' );
