<?php
/**
 * Open Graph and Twitter card metadata.
 *
 * Addresses come from WordPress. The host is the site URL, so production
 * uses the production address. This file does not fix a local host into the tags.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the 1200 by 630 crop used when an uploaded image is large enough.
 */
function lohkareet_register_social_image_size() {
	add_image_size( 'lohkareet-social', 1200, 630, true );
}
add_action( 'after_setup_theme', 'lohkareet_register_social_image_size' );

/**
 * Registers the page fields an editor can fill without writing HTML.
 */
function lohkareet_register_page_social_meta() {
	$fields = array(
		'lohkareet_social_description' => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_social_image'       => 'lohkareet_sanitize_image_id',
	);

	foreach ( $fields as $key => $sanitize_callback ) {
		register_post_meta(
			'page',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => 'lohkareet_page_social_meta_auth',
				'sanitize_callback' => $sanitize_callback,
			)
		);
	}
}
add_action( 'init', 'lohkareet_register_page_social_meta' );

/**
 * Limits page social meta to people who can edit the page.
 *
 * @param bool   $allowed   Whether the user can already access the meta.
 * @param string $meta_key  Meta key.
 * @param int    $object_id Post ID.
 */
function lohkareet_page_social_meta_auth( $allowed, $meta_key, $object_id, ...$unused ) {
	unset( $allowed, $meta_key, $unused );

	return current_user_can( 'edit_post', (int) $object_id );
}

/**
 * Adds the social fields to the page editor.
 */
function lohkareet_add_page_social_meta_box() {
	add_meta_box(
		'lohkareet_social_sharing',
		__( 'Social sharing', 'lohkareet' ),
		'lohkareet_render_page_social_meta_box',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_page', 'lohkareet_add_page_social_meta_box' );

/**
 * Prints the page social fields.
 *
 * @param WP_Post $post Page being edited.
 */
function lohkareet_render_page_social_meta_box( $post ) {
	lohkareet_render_social_fields( $post->ID, 'page' );
}

/**
 * Prints the social description and image fields.
 *
 * Event fields are saved with the rest of the event form. Page fields use
 * their own nonce.
 *
 * @param int    $post_id Post ID.
 * @param string $context event or page.
 */
function lohkareet_render_social_fields( $post_id, $context ) {
	$description = get_post_meta( $post_id, 'lohkareet_social_description', true );
	$image       = get_post_meta( $post_id, 'lohkareet_social_image', true );

	if ( ! is_string( $description ) ) {
		$description = '';
	}

	if ( ! is_string( $image ) ) {
		$image = '';
	}

	if ( 'page' === $context ) {
		wp_nonce_field( 'lohkareet_save_page_social_meta', 'lohkareet_page_social_nonce' );
	}
	$is_event = 'event' === $context;
	?>
	<fieldset<?php echo $is_event ? ' class="lohkareet-event-section"' : ''; ?>>
		<legend><strong><?php esc_html_e( 'Social sharing', 'lohkareet' ); ?></strong></legend>
		<p>
			<label for="lohkareet_social_description"><strong><?php esc_html_e( 'Social description', 'lohkareet' ); ?></strong><?php echo $is_event ? ' ' . lohkareet_event_scope_mark( 'lohkareet_social_description' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label><br>
			<textarea class="widefat" rows="3" id="lohkareet_social_description" name="lohkareet_social_description"><?php echo esc_textarea( $description ); ?></textarea>
			<?php if ( 'event' === $context ) : ?>
				<span class="description"><?php esc_html_e( 'Used for link previews. Leave this empty to build the text from the event’s known facts.', 'lohkareet' ); ?></span>
				<?php
				$automatic = lohkareet_event_social_description( $post_id );
				if ( '' !== $automatic ) {
					echo '<br><span class="description">' . esc_html(
						sprintf(
							/* translators: %s: description used when the field is empty. */
							__( 'If this stays empty, the preview uses: %s', 'lohkareet' ),
							$automatic
						)
					) . '</span>';
				}
				?>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'Used for link previews. Leave this empty to use the automatic description for this page.', 'lohkareet' ); ?></span>
			<?php endif; ?>
		</p>
		<p>
			<label for="lohkareet_social_image"><strong><?php esc_html_e( 'Social image', 'lohkareet' ); ?></strong><?php echo $is_event ? ' ' . lohkareet_event_scope_mark( 'lohkareet_social_image' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label><br>
			<input type="hidden" id="lohkareet_social_image" name="lohkareet_social_image" value="<?php echo esc_attr( $image ); ?>">
			<span id="lohkareet-social-image-preview">
				<?php
				if ( '' !== $image ) {
					echo wp_get_attachment_image( (int) $image, 'medium' );
				}
				?>
			</span><br>
			<button type="button" class="button" id="lohkareet-social-image-button"><?php esc_html_e( 'Select image', 'lohkareet' ); ?></button>
			<button type="button" class="button-link" id="lohkareet-social-image-remove"><?php esc_html_e( 'Remove image', 'lohkareet' ); ?></button><br>
			<?php if ( 'event' === $context ) : ?>
				<span class="description"><?php esc_html_e( 'A 1200 by 630 image for link previews. Leave this empty to use the featured image, then the series image.', 'lohkareet' ); ?></span>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'A 1200 by 630 image for link previews. Leave this empty to use the series image.', 'lohkareet' ); ?></span>
			<?php endif; ?>
		</p>
	</fieldset>
	<?php
}

/**
 * Saves social fields from the page editor.
 *
 * @param int $post_id Page ID.
 */
function lohkareet_save_page_social_meta( $post_id ) {
	if ( ! isset( $_POST['lohkareet_page_social_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['lohkareet_page_social_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'lohkareet_save_page_social_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'lohkareet_social_description' => 'lohkareet_sanitize_plain_textarea',
		'lohkareet_social_image'       => 'lohkareet_sanitize_image_id',
	);

	foreach ( $fields as $key => $sanitize_callback ) {
		$raw   = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$value = call_user_func( $sanitize_callback, $raw );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_page', 'lohkareet_save_page_social_meta' );

/**
 * Prints one Open Graph and Twitter card set.
 */
function lohkareet_print_social_meta() {
	if ( is_admin() || is_feed() || is_embed() || is_404() ) {
		return;
	}

	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;

	$title       = lohkareet_get_social_title();
	$description = lohkareet_get_social_description();
	$url         = lohkareet_get_social_url();
	$image       = lohkareet_get_social_image();
	$type        = is_singular( 'event' ) ? 'article' : 'website';
	$site_name   = lohkareet_social_plain_text( get_bloginfo( 'name' ) );

	if ( '' === $site_name ) {
		$site_name = 'Siirtymä – Lohkareet';
	}

	echo "\n";
	lohkareet_print_social_tag( 'property', 'og:title', $title, false );

	if ( '' !== $description ) {
		lohkareet_print_social_tag( 'property', 'og:description', $description, false );
	}

	if ( is_array( $image ) && '' !== $image['url'] ) {
		lohkareet_print_social_tag( 'property', 'og:image', $image['url'], true );

		if ( $image['width'] > 0 && $image['height'] > 0 ) {
			lohkareet_print_social_tag( 'property', 'og:image:width', (string) $image['width'], false );
			lohkareet_print_social_tag( 'property', 'og:image:height', (string) $image['height'], false );
		}
	}

	lohkareet_print_social_tag( 'property', 'og:type', $type, false );

	if ( '' !== $url ) {
		lohkareet_print_social_tag( 'property', 'og:url', $url, true );
	}

	lohkareet_print_social_tag( 'property', 'og:site_name', $site_name, false );
	lohkareet_print_social_tag( 'name', 'twitter:card', 'summary_large_image', false );
	lohkareet_print_social_tag( 'name', 'twitter:title', $title, false );

	if ( '' !== $description ) {
		lohkareet_print_social_tag( 'name', 'twitter:description', $description, false );
	}

	if ( is_array( $image ) && '' !== $image['url'] ) {
		lohkareet_print_social_tag( 'name', 'twitter:image', $image['url'], true );
	}

	if ( '' !== $url && ! is_singular() && ! is_front_page() && ( lohkareet_is_social_front() || is_post_type_archive( 'event' ) ) ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'lohkareet_print_social_meta', 1 );

/**
 * Prints one meta tag. Empty content is skipped.
 *
 * @param string $attribute property or name.
 * @param string $name      Tag name.
 * @param string $content   Tag content.
 * @param bool   $is_url    Whether the content is a URL.
 */
function lohkareet_print_social_tag( $attribute, $name, $content, $is_url ) {
	$content = $is_url ? esc_url( $content ) : esc_attr( $content );

	if ( '' === $content ) {
		return;
	}

	printf(
		'<meta %1$s="%2$s" content="%3$s" />' . "\n",
		esc_attr( $attribute ),
		esc_attr( $name ),
		$content
	);
}

/**
 * Social title for the current request.
 */
/**
 * Whether this request is the Finnish front page or a language home.
 *
 * The English home is the language root until an English front page exists.
 * Polylang serves that root as the posts index, so is_front_page() is false there.
 */
function lohkareet_is_social_front() {
	if ( is_front_page() ) {
		return true;
	}

	if ( ! function_exists( 'pll_home_url' ) || ! function_exists( 'pll_current_language' ) ) {
		return false;
	}

	$language = pll_current_language( 'slug' );

	if ( ! is_string( $language ) || '' === $language ) {
		return false;
	}

	$home = pll_home_url( $language );

	if ( ! is_string( $home ) || '' === $home ) {
		return false;
	}

	global $wp;

	$path      = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	$home_path = wp_parse_url( $home, PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? trim( $home_path, '/' ) : '';

	return $path === $home_path;
}

/**
 * Social title for the current request.
 */
function lohkareet_get_social_title() {
	$brand = 'Siirtymä – Lohkareet';

	if ( lohkareet_is_social_front() ) {
		return 'SIIRTYMÄ – LOHKAREET';
	}

	if ( is_singular( 'event' ) ) {
		$post_id = (int) get_queried_object_id();
		$name    = lohkareet_social_plain_text( lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' ) );

		if ( '' === $name ) {
			$name = lohkareet_social_plain_text( get_the_title( $post_id ) );
		}

		if ( '' === $name ) {
			return $brand;
		}

		return $name . ' — ' . $brand;
	}

	if ( is_singular() ) {
		$name = lohkareet_social_plain_text( get_the_title() );

		if ( '' !== $name ) {
			return $name . ' — ' . $brand;
		}
	}

	if ( is_post_type_archive( 'event' ) ) {
		return __( 'Events', 'lohkareet' ) . ' — ' . $brand;
	}

	return $brand;
}

/**
 * Social description for the current request.
 */
function lohkareet_get_social_description() {
	if ( is_singular() ) {
		$post_id = (int) get_queried_object_id();
		$custom  = lohkareet_get_saved_social_description( $post_id );

		if ( '' !== $custom ) {
			return $custom;
		}

		if ( lohkareet_is_social_front() ) {
			return lohkareet_front_social_description();
		}

		if ( is_singular( 'event' ) ) {
			return lohkareet_event_social_description( $post_id );
		}

		$page = lohkareet_page_social_description( $post_id );

		if ( '' !== $page ) {
			return $page;
		}
	}

	if ( lohkareet_is_social_front() ) {
		return lohkareet_front_social_description();
	}

	if ( is_post_type_archive( 'event' ) ) {
		return __( 'Nine events of ambient music and site-specific art at the erratics of Pirkanmaa.', 'lohkareet' );
	}

	return lohkareet_front_social_description();
}

/**
 * Canonical URL for the current language.
 */
function lohkareet_get_social_url() {
	if ( is_singular() && ! lohkareet_is_social_front() ) {
		$url = wp_get_canonical_url();

		if ( ! is_string( $url ) || '' === $url ) {
			$url = get_permalink();
		}

		return is_string( $url ) ? $url : '';
	}

	if ( lohkareet_is_social_front() ) {
		if ( function_exists( 'pll_home_url' ) ) {
			$language = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
			$url      = is_string( $language ) && '' !== $language ? pll_home_url( $language ) : pll_home_url();

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	if ( is_post_type_archive( 'event' ) ) {
		$url = get_post_type_archive_link( 'event' );

		return is_string( $url ) ? $url : '';
	}

	return home_url( '/' );
}

/**
 * Image for the current request.
 *
 * Events use a dedicated image, then the featured image, then the series image.
 * Other pages use a dedicated image, then the series image. A missing file is skipped.
 *
 * @return array{url: string, width: int, height: int}|null
 */
function lohkareet_get_social_image() {
	$post_id = is_singular() ? (int) get_queried_object_id() : 0;

	if ( $post_id > 0 ) {
		$dedicated = lohkareet_social_image_from_attachment( (int) get_post_meta( $post_id, 'lohkareet_social_image', true ) );

		if ( is_array( $dedicated ) ) {
			return $dedicated;
		}

		if ( is_singular( 'event' ) ) {
			$featured = lohkareet_social_image_from_attachment( (int) get_post_thumbnail_id( $post_id ) );

			if ( is_array( $featured ) ) {
				return $featured;
			}
		}
	}

	return lohkareet_get_global_social_image();
}

/**
 * Series image shipped with the theme. Null when the file is missing.
 *
 * @return array{url: string, width: int, height: int}|null
 */
function lohkareet_get_global_social_image() {
	$relative = 'assets/images/social-share.jpg';
	$path     = get_theme_file_path( $relative );

	if ( ! is_string( $path ) || ! is_file( $path ) ) {
		return null;
	}

	$size = getimagesize( $path );
	$url  = get_theme_file_uri( $relative );

	if ( ! is_array( $size ) || (int) $size[0] <= 0 || (int) $size[1] <= 0 || ! is_string( $url ) || '' === $url ) {
		return null;
	}

	return array(
		'url'    => $url,
		'width'  => (int) $size[0],
		'height' => (int) $size[1],
	);
}

/**
 * Attachment image, preferring the 1200 by 630 crop when that file exists.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{url: string, width: int, height: int}|null
 */
function lohkareet_social_image_from_attachment( $attachment_id ) {
	$attachment_id = (int) $attachment_id;

	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return null;
	}

	$original   = get_attached_file( $attachment_id );
	$metadata   = wp_get_attachment_metadata( $attachment_id );
	$candidates = array();

	if ( is_string( $original ) && is_file( $original ) && is_array( $metadata ) && ! empty( $metadata['sizes']['lohkareet-social']['file'] ) ) {
		$intermediate = path_join( dirname( $original ), (string) $metadata['sizes']['lohkareet-social']['file'] );
		$src          = wp_get_attachment_image_src( $attachment_id, 'lohkareet-social' );

		if ( is_file( $intermediate ) && is_array( $src ) && ! empty( $src[0] ) ) {
			$candidates[] = array( $intermediate, (string) $src[0] );
		}
	}

	$full = wp_get_attachment_image_src( $attachment_id, 'full' );

	if ( is_string( $original ) && is_file( $original ) && is_array( $full ) && ! empty( $full[0] ) ) {
		$candidates[] = array( $original, (string) $full[0] );
	}

	foreach ( $candidates as $candidate ) {
		$size = getimagesize( $candidate[0] );

		if ( ! is_array( $size ) || (int) $size[0] <= 0 || (int) $size[1] <= 0 || '' === $candidate[1] ) {
			continue;
		}

		return array(
			'url'    => $candidate[1],
			'width'  => (int) $size[0],
			'height' => (int) $size[1],
		);
	}

	return null;
}

/**
 * Saved description, only when it belongs to the language being viewed.
 *
 * @param int $post_id Post ID.
 */
function lohkareet_get_saved_social_description( $post_id ) {
	if ( ! lohkareet_social_post_matches_language( $post_id ) ) {
		return '';
	}

	$text = get_post_meta( $post_id, 'lohkareet_social_description', true );

	if ( ! is_string( $text ) ) {
		return '';
	}

	return lohkareet_social_limit( lohkareet_social_plain_text( $text ) );
}

/**
 * Front-page description in the current language.
 */
function lohkareet_front_social_description() {
	return __( 'Siirtymä – Lohkareet brings nine events of ambient music and site-specific art to the erratics, stone formations, and surrounding landscapes of Pirkanmaa.', 'lohkareet' );
}

/**
 * Description for Lohkareista or Varustautuminen.
 *
 * A translated page is recognized through its Finnish slug. Stored Finnish
 * prose is not reused once the request is in another language.
 *
 * @param int $post_id Page ID.
 */
function lohkareet_page_social_description( $post_id ) {
	if ( ! lohkareet_social_post_matches_language( $post_id ) ) {
		return lohkareet_front_social_description();
	}

	$key = lohkareet_social_page_key( $post_id );

	if ( 'lohkareista' === $key ) {
		return __( 'Siirtymä – Lohkareet is a series of nine events in Pirkanmaa, produced by Suomen Ambientyhdistys. The events take place with erratics, stone formations, and the landscapes around them.', 'lohkareet' );
	}

	if ( 'varustautuminen' === $key ) {
		return __( 'Lohkareet events take place in natural surroundings through autumn and winter. Dress for a forest visit, and bring your own lamp.', 'lohkareet' );
	}

	return '';
}

/**
 * Page slug used for an automatic description, across linked translations.
 *
 * @param int $post_id Page ID.
 */
function lohkareet_social_page_key( $post_id ) {
	$slugs = array();
	$post  = get_post( $post_id );

	if ( $post instanceof WP_Post && '' !== $post->post_name ) {
		$slugs[] = $post->post_name;
	}

	if ( function_exists( 'pll_get_post_translations' ) ) {
		$translations = pll_get_post_translations( $post_id );

		if ( is_array( $translations ) ) {
			foreach ( $translations as $translated_id ) {
				$translated = get_post( (int) $translated_id );

				if ( $translated instanceof WP_Post && '' !== $translated->post_name ) {
					$slugs[] = $translated->post_name;
				}
			}
		}
	}

	foreach ( array( 'lohkareista', 'varustautuminen' ) as $key ) {
		if ( in_array( $key, $slugs, true ) ) {
			return $key;
		}
	}

	return '';
}

/**
 * Event description built from facts that are actually known.
 *
 * Tentative and unannounced artists are left out. Placeholder words are left out.
 * Location prose is included only in the language of the post being viewed.
 *
 * @param int $post_id Event ID.
 */
function lohkareet_event_social_description( $post_id ) {
	$facts = array();

	foreach (
		array(
			lohkareet_get_event_meta_string( $post_id, 'lohkareet_municipality' ),
			lohkareet_get_event_date_display( $post_id ),
			lohkareet_get_event_meta_string( $post_id, 'lohkareet_venue' ),
		) as $fact
	) {
		$fact = lohkareet_social_plain_text( $fact );

		if ( '' !== $fact ) {
			$facts[] = $fact;
		}
	}

	$parts = array();

	if ( $facts ) {
		$parts[] = implode( ' · ', $facts );
	}

	$status = lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_status' );
	$artist = lohkareet_social_plain_text( lohkareet_get_event_meta_string( $post_id, 'lohkareet_artist_name' ) );

	if ( 'confirmed' === $status && '' !== $artist ) {
		$parts[] = $artist;
	}

	if ( lohkareet_social_post_matches_language( $post_id ) ) {
		$context = lohkareet_social_first_sentence( lohkareet_get_event_meta_string( $post_id, 'lohkareet_location_description' ) );

		if ( '' !== $context ) {
			$parts[] = $context;
		}
	}

	if ( ! $parts ) {
		return lohkareet_front_social_description();
	}

	$description = implode( '. ', $parts );

	if ( ! preg_match( '/[.!?]$/u', $description ) ) {
		$description .= '.';
	}

	return lohkareet_social_limit( $description );
}

/**
 * Whether stored prose on this post may be used for the current language.
 *
 * Without Polylang, the post is the current language. With Polylang, Finnish
 * text stays on the Finnish post.
 *
 * @param int $post_id Post ID.
 */
function lohkareet_social_post_matches_language( $post_id ) {
	if ( ! function_exists( 'pll_current_language' ) || ! function_exists( 'pll_get_post_language' ) ) {
		return true;
	}

	$current       = pll_current_language( 'slug' );
	$post_language = pll_get_post_language( (int) $post_id, 'slug' );

	if ( ! is_string( $current ) || '' === $current || ! is_string( $post_language ) || '' === $post_language ) {
		return true;
	}

	return $current === $post_language;
}

/**
 * Collapses text and drops a value that is only a placeholder.
 *
 * @param string $text Raw text.
 */
function lohkareet_social_plain_text( $text ) {
	$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );

	if ( '' === $text ) {
		return '';
	}

	$placeholder = remove_accents( strtolower( $text ) );
	$blocked     = array( 'tba', 'tbc', 'tbd', 'unknown', 'n/a', 'na', 'ei tiedossa' );

	if ( in_array( $placeholder, $blocked, true ) ) {
		return '';
	}

	return $text;
}

/**
 * First sentence of a location description.
 *
 * @param string $text Location description.
 */
function lohkareet_social_first_sentence( $text ) {
	$text = lohkareet_social_plain_text( $text );

	if ( '' === $text ) {
		return '';
	}

	if ( preg_match( '/^(.+?[.!?])(?:\s|$)/u', $text, $matches ) ) {
		$text = $matches[1];
	}

	return lohkareet_social_limit( $text, 220 );
}

/**
 * Keeps a description inside a length link previews can show.
 *
 * @param string $text  Description.
 * @param int    $limit Maximum characters.
 */
function lohkareet_social_limit( $text, $limit = 400 ) {
	$text = trim( $text );

	if ( '' === $text || mb_strlen( $text ) <= $limit ) {
		return $text;
	}

	$cut   = mb_substr( $text, 0, $limit - 1 );
	$space = mb_strrpos( $cut, ' ' );

	if ( false !== $space && $space > (int) ( $limit * 0.6 ) ) {
		$cut = mb_substr( $cut, 0, $space );
	}

	return rtrim( $cut, " .,;:" ) . '…';
}
