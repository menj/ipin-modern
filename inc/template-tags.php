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
				<span <?php comment_class(); ?>><?php comment_author_link(); ?></span>
			</strong>
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
   LIGHTBOX DATA AJAX
   Returns pin metadata for the lightbox overlay.
   No social data — design-only build.
   ------------------------------------------------------- */
add_action( 'wp_ajax_ipin_lightbox_data',        'ipin_lightbox_data_handler' );
add_action( 'wp_ajax_nopriv_ipin_lightbox_data', 'ipin_lightbox_data_handler' );

function ipin_lightbox_data_handler(): void {
	$post_id = (int) ( $_POST['post_id'] ?? 0 );
	if ( ! $post_id ) {
		wp_send_json_error( 'Invalid post', 400 );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		wp_send_json_error( 'Not found', 404 );
	}

	// Image
	$img_url = '';
	if ( has_post_thumbnail( $post_id ) ) {
		$src     = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
		$img_url = $src ? $src[0] : '';
	}

	// Video meta
	$is_video  = (bool) get_post_meta( $post_id, '_ipin_is_video', true );
	$embed_url = (string) get_post_meta( $post_id, '_ipin_video_embed_url', true );

	// Author
	$author_id     = (int) $post->post_author;
	$author_data   = get_userdata( $author_id );
	$author_name   = $author_data ? $author_data->display_name : '';
	$author_url    = get_author_posts_url( $author_id );
	$author_avatar = get_avatar_url( $author_id, [ 'size' => 32 ] );

	// Comments (latest 3)
	$raw_comments = get_comments( [
		'post_id' => $post_id,
		'status'  => 'approve',
		'number'  => 3,
		'order'   => 'DESC',
	] );
	$comments = array_map( static function ( \WP_Comment $c ): array {
		return [
			'author' => esc_html( $c->comment_author ),
			'text'   => esc_html( wp_strip_all_tags( $c->comment_content ) ),
			'avatar' => get_avatar_url( $c->comment_author_email, [ 'size' => 28 ] ),
		];
	}, $raw_comments );

	wp_send_json_success( [
		'post_id'       => $post_id,
		'title'         => get_the_title( $post_id ),
		'permalink'     => get_permalink( $post_id ),
		'img_url'       => $img_url,
		'is_video'      => $is_video,
		'embed_url'     => $embed_url,
		'author_name'   => esc_html( $author_name ),
		'author_url'    => esc_url( $author_url ),
		'author_avatar' => esc_url( $author_avatar ),
		'date'          => get_the_date( get_option( 'date_format' ), $post_id ),
		'description'   => esc_html( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ),
		'source_url'    => esc_url( (string) get_post_meta( $post_id, '_ipin_source_url', true ) ),
		'comments'      => $comments,
	] );
}
