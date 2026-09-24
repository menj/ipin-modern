<?php
/**
 * iPin Modern — Template Tags & Helper Functions
 *
 * Functions the templates and template-parts/ call: options,
 * icons, card images and comment previews, timestamps, the
 * comment callback and form fields, and the 404 page's lines.
 * RSS output lives in feed.php, the lightbox endpoint in
 * rest-api.php.
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
   PLAIN TEXT
   HTML to plain text: tags stripped, entities decoded. For
   output that is not HTML, such as JSON-LD and the lightbox
   REST data, where entities would show up literally.
   ------------------------------------------------------- */
function ipin_plain( string $html ): string {
	return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
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
		return sprintf( _n( '%d min ago', '%d mins ago', $n, 'ipin-modern' ), $n );
	}

	if ( $diff <= 86400 ) {
		$n = max( 1, (int) round( $diff / 3600 ) );
		/* translators: %d = number of hours */
		return sprintf( _n( '%d hour ago', '%d hours ago', $n, 'ipin-modern' ), $n );
	}

	if ( $diff <= 31536000 ) {
		$n = max( 1, (int) round( $diff / 86400 ) );
		/* translators: %d = number of days */
		return sprintf( _n( '%d day ago', '%d days ago', $n, 'ipin-modern' ), $n );
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
				'reply_text' => __( 'Reply', 'ipin-modern' ),
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
				<span class="comment-author-badge"><?php esc_html_e( 'Author', 'ipin-modern' ); ?></span>
			<?php endif; ?>
			&mdash;
			<?php comment_date( 'j M Y g:ia' ); ?>
			<a href="#comment-<?php comment_ID(); ?>" title="<?php esc_attr_e( 'Permalink', 'ipin-modern' ); ?>">#</a>
			<?php edit_comment_link( __( 'Edit', 'ipin-modern' ), ' ', '' ); ?>

			<?php if ( '0' === $comment->comment_approved ) : ?>
				<br><em><?php esc_html_e( 'Your comment is awaiting moderation.', 'ipin-modern' ); ?></em>
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
		. '<label for="author">' . esc_html__( 'Name', 'ipin-modern' ) . $required . '</label>'
		. '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $aria . ' autocomplete="name">'
		. '</div>';

	$fields['email'] =
		'<div>'
		. '<label for="email">' . esc_html__( 'Email', 'ipin-modern' ) . $required . '</label>'
		. '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $aria . ' autocomplete="email">'
		. '</div>';

	$fields['url'] =
		'<div>'
		. '<label for="url">' . esc_html__( 'Website', 'ipin-modern' ) . '</label>'
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
   404 PAGE
   A cheeky headline and line, picked at random on each load.
   Add your own (or replace these) with the ipin_404_quips
   filter; each entry is [ 'title' => …, 'text' => … ].
   ------------------------------------------------------- */
function ipin_404_quips(): array {
	$quips = [
		[
			'title' => __( 'This pin fell off the board.', 'ipin-modern' ),
			'text'  => __( 'We checked behind the sofa. Crumbs, one hair tie, no page. Try a search, or slip back to the board before anyone notices.', 'ipin-modern' ),
		],
		[
			'title' => __( 'Well, this is awkward.', 'ipin-modern' ),
			'text'  => __( 'You found the one spot on the board with nothing pinned to it. Honestly? Impressive.', 'ipin-modern' ),
		],
		[
			'title' => __( 'Nothing to see here. Literally.', 'ipin-modern' ),
			'text'  => __( "The page you wanted has left the building and didn't leave a forwarding address. Rude, we know.", 'ipin-modern' ),
		],
		[
			'title' => __( 'Somebody un-pinned this.', 'ipin-modern' ),
			'text'  => __( 'Either the link is wrong or the page took a gap year to find itself. Neither of us is getting it back today.', 'ipin-modern' ),
		],
		[
			'title' => __( "Plot twist: there's no page.", 'ipin-modern' ),
			'text'  => __( "You clicked with confidence, and we respect that. Sadly, the page didn't turn up to its own party.", 'ipin-modern' ),
		],
		[
			'title' => __( 'Blank is the new black.', 'ipin-modern' ),
			'text'  => __( 'Very minimalist of us. Still, you probably came here for something with pictures in it.', 'ipin-modern' ),
		],
	];
	return (array) apply_filters( 'ipin_404_quips', $quips );
}

function ipin_404_quip(): array {
	$quips = array_values( array_filter(
		ipin_404_quips(),
		static fn( $q ): bool => is_array( $q ) && '' !== trim( (string) ( $q['title'] ?? '' ) )
	) );
	if ( ! $quips ) {
		return [ 'title' => __( 'Page not found', 'ipin-modern' ), 'text' => '' ];
	}
	$quip = $quips[ wp_rand( 0, count( $quips ) - 1 ) ];
	return [ 'title' => (string) $quip['title'], 'text' => (string) ( $quip['text'] ?? '' ) ];
}

/**
 * Permalink of a random pin from the 50 newest public ones, for the
 * 404 page's "random pin" button. Empty string when there are none.
 */
function ipin_random_pin_url(): string {
	$ids = get_posts( [
		'numberposts'  => 50,
		'fields'       => 'ids',
		'has_password' => false,
	] );
	return $ids ? (string) get_permalink( $ids[ array_rand( $ids ) ] ) : '';
}
