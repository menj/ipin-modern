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
		return $n === 1
			? sprintf( __( '%d min ago', 'ipin' ), $n )
			: sprintf( __( '%d mins ago', 'ipin' ), $n );
	}

	if ( $diff <= 86400 ) {
		$n = max( 1, (int) round( $diff / 3600 ) );
		/* translators: %d = number of hours */
		return $n === 1
			? sprintf( __( '%d hour ago', 'ipin' ), $n )
			: sprintf( __( '%d hours ago', 'ipin' ), $n );
	}

	if ( $diff <= 31536000 ) {
		$n = max( 1, (int) round( $diff / 86400 ) );
		/* translators: %d = number of days */
		return $n === 1
			? sprintf( __( '%d day ago', 'ipin' ), $n )
			: sprintf( __( '%d days ago', 'ipin' ), $n );
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
