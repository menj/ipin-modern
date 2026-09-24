<?php
/**
 * iPin Modern — Template Tags & Helper Functions
 *
 * Theme-specific functions used in template files.
 * Keeps functions.php lean and templates readable.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   OPTION HELPER
   Checks wp_options (admin page) first, falls back to
   theme_mod (Customizer), then the supplied default.
   ------------------------------------------------------- */
function ipin_option( string $key, mixed $default = '' ): mixed {
	$val = get_option( $key, null );
	return ( null !== $val ) ? $val : get_theme_mod( $key, $default );
}


/* -------------------------------------------------------
   SOCIAL PLATFORM ICON
   Returns the bundled minimalist SVG glyph for a platform
   (assets/img/social/{platform}.svg), inlined with
   fill="currentColor" so it follows the surrounding text
   colour in every colour scheme and in dark mode.
   ------------------------------------------------------- */
function ipin_social_icon( string $platform ): string {
	static $cache = [];

	if ( isset( $cache[ $platform ] ) ) {
		return $cache[ $platform ];
	}

	$file = get_template_directory() . '/assets/img/social/' . sanitize_key( $platform ) . '.svg';
	$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : '';

	if ( $svg ) {
		$svg = str_replace(
			'<svg ',
			'<svg class="ipin-social-svg" fill="currentColor" aria-hidden="true" focusable="false" ',
			$svg
		);
	}

	return $cache[ $platform ] = $svg;
}


/* -------------------------------------------------------
   CARD IMAGE
   The attachment a grid card shows: the featured image, else
   the first image attached to the post. The fallback lookup is
   a query, so its answer (an ID, or 0 for none) is kept in
   post meta — which the main loop already primes — and redone
   only when the post or its attachments change.
   ------------------------------------------------------- */
function ipin_card_image_id( \WP_Post $post ): int {
	$thumb = (int) get_post_thumbnail_id( $post );
	if ( $thumb ) {
		return $thumb;
	}

	$cached = get_post_meta( $post->ID, '_ipin_card_image', true );
	if ( '' === $cached ) {
		$kids   = get_children( [
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'numberposts'    => 1,
			'fields'         => 'ids',
		] );
		$cached = $kids ? (int) reset( $kids ) : 0;
		update_post_meta( $post->ID, '_ipin_card_image', $cached );
	}
	return (int) $cached;
}

function ipin_forget_card_image( int $post_id ): void {
	delete_post_meta( $post_id, '_ipin_card_image' );
}
add_action( 'save_post', 'ipin_forget_card_image' );
add_action( 'add_attachment', static fn( int $id ) => ipin_forget_card_image( (int) wp_get_post_parent_id( $id ) ) );
add_action( 'delete_attachment', static fn( int $id ) => ipin_forget_card_image( (int) wp_get_post_parent_id( $id ) ) );


/* -------------------------------------------------------
   GRID COMMENT PREVIEWS
   The newest $per_post approved comments for every card on
   the page, fetched together instead of one query per card.
   Posts with many comments are queried on their own with a
   limit, so one busy post can't bloat the shared query.
   Returns [ post_id => WP_Comment[] ].
   ------------------------------------------------------- */
function ipin_comment_previews( array $posts, int $per_post ): array {
	$previews = [];
	if ( $per_post < 1 ) {
		return $previews;
	}

	$batched = [];
	foreach ( $posts as $p ) {
		$count = (int) $p->comment_count;   // approved comments only
		if ( 0 === $count ) {
			continue;
		}
		if ( $count <= 25 ) {
			$batched[] = (int) $p->ID;
		} else {
			$previews[ $p->ID ] = get_comments( [
				'post_id' => $p->ID,
				'status'  => 'approve',
				'number'  => $per_post,
			] );
		}
	}

	if ( $batched ) {
		$all = get_comments( [
			'post__in'                  => $batched,
			'status'                    => 'approve',
			'orderby'                   => 'comment_date_gmt',
			'order'                     => 'DESC',
			'update_comment_meta_cache' => false,
		] );
		foreach ( $all as $c ) {
			$pid = (int) $c->comment_post_ID;
			if ( count( $previews[ $pid ] ?? [] ) < $per_post ) {
				$previews[ $pid ][] = $c;
			}
		}
	}

	return $previews;
}


/* -------------------------------------------------------
   UI ICONS
   Minimal 2px line icons, inlined so they inherit colour and
   size (1em) from the text around them. Replaces the Font
   Awesome CDN stylesheet; brand glyphs live in
   assets/img/social/ (see ipin_social_icon()).
   ------------------------------------------------------- */
function ipin_icon( string $name ): string {
	static $paths = [
		'fire'        => '<path d="M12 3c.6 3.1-2.2 4.6-2.2 7.3a2.2 2.2 0 0 0 4.4 0c0-.9-.4-1.9-.4-1.9 2.3 1.3 3.9 3.5 3.9 6a5.7 5.7 0 0 1-11.4 0C6.3 9.6 12 7.6 12 3Z"/>',
		'chart-line'  => '<path d="M4 20h16"/><path d="m5 15 4.5-4.5 3.5 3.5L19 8"/><path d="M15 8h4v4"/>',
		'crown'       => '<path d="M5 19h14"/><path d="M5 16 3.5 7l5 3.5L12 5l3.5 5.5 5-3.5L19 16Z"/>',
		'comment'     => '<path d="M20.5 12a8.5 8.5 0 0 1-12.2 7.6L3.5 21l1.4-4.6A8.5 8.5 0 1 1 20.5 12Z"/>',
		'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
		'link'        => '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3A4.5 4.5 0 0 0 13 4.6l-1.2 1.2"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1.2-1.2"/>',
		'chevron-up'  => '<path d="m6 15 6-6 6 6"/>',
		'search'      => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
		'rss'         => '<path d="M5 4.5A14.5 14.5 0 0 1 19.5 19"/><path d="M5 10.5a8.5 8.5 0 0 1 8.5 8.5"/><circle cx="6" cy="18" r="1.5" fill="currentColor" stroke="none"/>',
	];
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="ipin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}


/* -------------------------------------------------------
   RELATIVE HUMAN-READABLE TIMESTAMP
   ------------------------------------------------------- */
function ipin_human_time_diff( int $from, int $to = 0 ): string {
	if ( ! $to ) {
		$to = time();
	}

	$diff = (int) abs( $to - $from );

	if ( $diff <= 3600 ) {
		$n = max( 1, (int) round( $diff / 60 ) );
		/* translators: %d = number of minutes */
		return sprintf( _n( '%d min ago', '%d mins ago', $n, 'ipin' ), $n );
	}

	if ( $diff <= 86400 ) {
		$n = max( 1, (int) round( $diff / 3600 ) );
		/* translators: %d = number of hours */
		return sprintf( _n( '%d hour ago', '%d hours ago', $n, 'ipin' ), $n );
	}

	if ( $diff <= 31536000 ) {
		$n = max( 1, (int) round( $diff / 86400 ) );
		/* translators: %d = number of days */
		return sprintf( _n( '%d day ago', '%d days ago', $n, 'ipin' ), $n );
	}

	return (string) get_the_date();
}


/* -------------------------------------------------------
   COMMENT CALLBACK
   Used by wp_list_comments() in comments.php.
   ------------------------------------------------------- */
function ipin_comment( \WP_Comment $comment, array $args, int $depth ): void {
	$show_avatars = (bool) get_option( 'show_avatars' );
	?>
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">

		<?php if ( $show_avatars ) : ?>
			<div class="comment-avatar">
				<?php echo get_avatar( get_comment_author_email(), 36 ); ?>
			</div>
		<?php endif; ?>

		<div class="comment-reply-wrap">
			<?php comment_reply_link( [
				'reply_text' => __( 'Reply', 'ipin' ),
				'depth'      => $depth,
				'max_depth'  => $args['max_depth'],
			] ); ?>
		</div>

		<div class="comment-content<?php echo $show_avatars ? ' comment-content-with-avatar' : ''; ?>">
			<strong>
				<span class="comment-author-name"><?php comment_author_link(); ?></span>
			</strong>
			<?php
			// Same test core uses for the li's .bypostauthor class.
			$post_author = (int) get_post_field( 'post_author', (int) $comment->comment_post_ID );
			if ( $comment->user_id && (int) $comment->user_id === $post_author ) : ?>
				<span class="comment-author-badge"><?php esc_html_e( 'Author', 'ipin' ); ?></span>
			<?php endif; ?>
			&mdash;
			<?php comment_date( 'j M Y g:ia' ); ?>
			<a href="#comment-<?php comment_ID(); ?>" title="<?php esc_attr_e( 'Permalink', 'ipin' ); ?>">#</a>
			<?php edit_comment_link( __( 'Edit', 'ipin' ), ' ', '' ); ?>

			<?php if ( '0' === $comment->comment_approved ) : ?>
				<br><em><?php esc_html_e( 'Your comment is awaiting moderation.', 'ipin' ); ?></em>
			<?php endif; ?>

			<?php comment_text(); ?>
		</div>

	<?php
	// Note: closing </li> is handled by WordPress
}


/* -------------------------------------------------------
   COMMENT FORM FIELD STYLES
   ------------------------------------------------------- */
function ipin_comment_form_fields( array $fields ): array {
	$commenter = wp_get_current_commenter();
	$req       = (bool) get_option( 'require_name_email' );
	$aria      = $req ? " aria-required='true'" : '';
	$required  = $req ? ' *' : '';

	$fields['author'] =
		'<div>'
		. '<label for="author">' . esc_html__( 'Name', 'ipin' ) . $required . '</label>'
		. '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $aria . ' autocomplete="name">'
		. '</div>';

	$fields['email'] =
		'<div>'
		. '<label for="email">' . esc_html__( 'Email', 'ipin' ) . $required . '</label>'
		. '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $aria . ' autocomplete="email">'
		. '</div>';

	$fields['url'] =
		'<div>'
		. '<label for="url">' . esc_html__( 'Website', 'ipin' ) . '</label>'
		. '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" autocomplete="url">'
		. '</div>';

	// Name / Email / Website sit in a three-column grid. The wrapper opens
	// in the first field and closes in the last, so core's cookie-consent
	// field (printed after these) stays full width below the grid.
	$fields['author'] = '<div class="comment-form-fields-grid">' . $fields['author'];
	$fields['url']   .= '</div>';

	return $fields;
}
add_filter( 'comment_form_default_fields', 'ipin_comment_form_fields' );


/* -------------------------------------------------------
   RSS FEED — PREPEND FEATURED IMAGE
   ------------------------------------------------------- */
function ipin_feed_content( string $content ): string {
	global $post;
	$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
	if ( ! empty( $img[0] ) ) {
		$content = '<p><a href="' . esc_url( get_permalink( $post->ID ) ) . '">'
			. '<img src="' . esc_url( $img[0] ) . '" alt=""></a></p>'
			. $content;
	}
	return $content;
}
add_filter( 'the_excerpt_rss',  'ipin_feed_content' );
add_filter( 'the_content_feed', 'ipin_feed_content' );


/* -------------------------------------------------------
   RSS FEED — ENCLOSURE
   One <enclosure> per item so feed readers show the pin's
   media: the video file for a video pin, otherwise the
   featured image at 'large' size. The length is taken from
   the exact file the URL points at (0 when it lives on
   another host, as RSS allows for an unknown size).
   ------------------------------------------------------- */
function ipin_feed_enclosure(): void {
	$post_id = (int) get_the_ID();

	$video = ipin_post_video( $post_id );
	if ( $video && 'file' === $video['type'] ) {
		$path = ipin_local_upload_path( $video['src'] );
		printf(
			'<enclosure url="%s" length="%d" type="%s" />' . "\n",
			esc_url( $video['src'] ),
			$path ? (int) filesize( $path ) : 0,
			esc_attr( $video['mime'] )
		);
		return;
	}

	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		return;
	}
	$src = wp_get_attachment_image_src( $thumb, 'large' );
	if ( ! $src ) {
		return;
	}
	$path = ipin_local_upload_path( $src[0] );
	printf(
		'<enclosure url="%s" length="%d" type="%s" />' . "\n",
		esc_url( $src[0] ),
		$path ? (int) filesize( $path ) : 0,
		esc_attr( (string) get_post_mime_type( $thumb ) )
	);
}
add_action( 'rss2_item', 'ipin_feed_enclosure' );

/** Filesystem path for a URL inside this site's uploads, or '' if it isn't one or doesn't exist. */
function ipin_local_upload_path( string $url ): string {
	$uploads = wp_get_upload_dir();
	$base    = trailingslashit( $uploads['baseurl'] );
	if ( ! str_starts_with( $url, $base ) ) {
		return '';
	}
	$path = trailingslashit( $uploads['basedir'] ) . rawurldecode( substr( $url, strlen( $base ) ) );
	$real = realpath( $path );
	// Guard against ../ escaping the uploads directory.
	return ( $real && str_starts_with( $real, realpath( $uploads['basedir'] ) ) && is_file( $real ) ) ? $real : '';
}


/* -------------------------------------------------------
   BODY CLASSES
   ------------------------------------------------------- */
function ipin_body_classes( array $classes ): array {
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}
	return $classes;
}
add_filter( 'body_class', 'ipin_body_classes' );


/* -------------------------------------------------------
   LIGHTBOX DATA — REST
   GET /wp-json/ipin/v1/pin/{id} (or ?rest_route= on plain
   permalinks). Public, read-only and cacheable, unlike the
   admin-ajax POST it replaces. Text fields are plain text and
   URLs are raw: the lightbox writes them with textContent and
   DOM properties, so HTML-escaping here would double-escape.
   ------------------------------------------------------- */
function ipin_plain( string $html ): string {
	return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

/**
 * Lightbox data for a publicly viewable pin, or null. Password-
 * protected posts are always refused: a cacheable response must
 * never carry content unlocked by one visitor's password cookie.
 */
function ipin_lightbox_payload( int $post_id ): ?array {
	$post = get_post( $post_id );
	if ( ! $post || ! is_post_publicly_viewable( $post ) || '' !== $post->post_password ) {
		return null;
	}

	$img_url = '';
	if ( has_post_thumbnail( $post ) ) {
		$src     = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'large' );
		$img_url = $src ? $src[0] : '';
	}

	$video     = ipin_post_video( $post_id );
	$author_id = (int) $post->post_author;
	$author    = get_userdata( $author_id );

	$comments = array_map( static fn( \WP_Comment $c ): array => [
		'author' => ipin_plain( $c->comment_author ),
		'text'   => ipin_plain( $c->comment_content ),
		'avatar' => (string) get_avatar_url( $c->comment_author_email, [ 'size' => 28 ] ),
	], get_comments( [
		'post_id' => $post_id,
		'status'  => 'approve',
		'number'  => 3,
	] ) );

	return [
		'post_id'       => $post_id,
		'title'         => ipin_plain( get_the_title( $post ) ),
		'permalink'     => esc_url_raw( (string) get_permalink( $post ) ),
		'img_url'       => esc_url_raw( $img_url ),
		'video'         => $video ? [
			'type' => $video['type'],
			'src'  => esc_url_raw( $video['src'] ),
			'mime' => $video['mime'],
		] : null,
		'author_name'   => $author ? ipin_plain( $author->display_name ) : '',
		'author_url'    => esc_url_raw( get_author_posts_url( $author_id ) ),
		'author_avatar' => esc_url_raw( (string) get_avatar_url( $author_id, [ 'size' => 32 ] ) ),
		'date'          => ipin_plain( (string) get_the_date( '', $post ) ),
		'description'   => ipin_plain( get_the_excerpt( $post ) ),
		'source_url'    => esc_url_raw( (string) get_post_meta( $post_id, '_ipin_source_url', true ) ),
		'comments'      => $comments,
	];
}

function ipin_rest_pin( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
	$payload = ipin_lightbox_payload( (int) $request['id'] );
	if ( ! $payload ) {
		return new \WP_Error( 'ipin_pin_not_found', __( 'Pin not found.', 'ipin' ), [ 'status' => 404 ] );
	}
	$response = rest_ensure_response( $payload );
	$response->header( 'Cache-Control', 'public, max-age=300' );
	return $response;
}

add_action( 'rest_api_init', static function (): void {
	register_rest_route( 'ipin/v1', '/pin/(?P<id>\d+)', [
		'methods'             => \WP_REST_Server::READABLE,
		'callback'            => 'ipin_rest_pin',
		'permission_callback' => '__return_true',   // public data only; see ipin_lightbox_payload()
		'args'                => [
			'id' => [ 'validate_callback' => static fn( $v ): bool => is_numeric( $v ) && (int) $v > 0 ],
		],
	] );
} );
