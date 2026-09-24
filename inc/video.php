<?php
/**
 * iPin Modern — Video Pins
 *
 * A post becomes a video pin when its "Video pin" box holds a URL.
 * Two kinds of source are playable:
 *
 *   file  — a direct video file (.mp4 / .webm / .ogv / .m4v / .mov)
 *           from any http(s) host, e.g. https://menj.bio/…/clip.mp4.
 *           Played in a native <video> element with the featured
 *           image as the poster. Hosts that forbid framing (menj.bio
 *           sends X-Frame-Options: SAMEORIGIN) work fine this way.
 *   embed — a YouTube or Vimeo link, normalised to the provider's
 *           embed player (YouTube via youtube-nocookie.com).
 *
 * Anything else is stored but not played; the editor box says so.
 * Meta keys (_ipin_video_embed_url, _ipin_is_video) are unchanged
 * from earlier versions, so existing video pins keep working.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   SOURCE PARSER
   Returns [ 'type' => 'file'|'embed', 'src' => url,
   'mime' => string, 'label' => string ] or null.
   ------------------------------------------------------- */
function ipin_video_source( string $url ): ?array {
	$url = esc_url_raw( trim( $url ), [ 'http', 'https' ] );
	if ( '' === $url ) {
		return null;
	}

	$parts = wp_parse_url( $url );
	$host  = strtolower( (string) ( $parts['host'] ?? '' ) );
	$path  = (string) ( $parts['path'] ?? '' );
	$host  = preg_replace( '/^(www\.|m\.)/', '', $host );

	// Direct video file.
	$ft = wp_check_filetype( basename( $path ), [
		'mp4'  => 'video/mp4',
		'm4v'  => 'video/mp4',
		'mov'  => 'video/quicktime',
		'webm' => 'video/webm',
		'ogv'  => 'video/ogg',
	] );
	if ( $ft['type'] ) {
		return [
			'type'  => 'file',
			'src'   => $url,
			'mime'  => $ft['type'],
			'label' => __( 'Video file', 'ipin-modern' ),
		];
	}

	// YouTube: watch?v=, youtu.be/, /shorts/, /embed/.
	$yt_id = '';
	if ( 'youtu.be' === $host ) {
		$yt_id = trim( $path, '/' );
	} elseif ( in_array( $host, [ 'youtube.com', 'youtube-nocookie.com' ], true ) ) {
		if ( preg_match( '#^/(?:embed|shorts|live)/([^/?]+)#', $path, $m ) ) {
			$yt_id = $m[1];
		} else {
			parse_str( (string) ( $parts['query'] ?? '' ), $q );
			$yt_id = (string) ( $q['v'] ?? '' );
		}
	}
	if ( $yt_id && preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $yt_id ) ) {
		return [
			'type'  => 'embed',
			'src'   => 'https://www.youtube-nocookie.com/embed/' . $yt_id,
			'mime'  => '',
			'label' => 'YouTube',
		];
	}

	// Vimeo: vimeo.com/123 or player.vimeo.com/video/123.
	if ( in_array( $host, [ 'vimeo.com', 'player.vimeo.com' ], true )
		&& preg_match( '#/(?:video/)?(\d{5,12})(?:/|$)#', $path, $m ) ) {
		return [
			'type'  => 'embed',
			'src'   => 'https://player.vimeo.com/video/' . $m[1],
			'mime'  => '',
			'label' => 'Vimeo',
		];
	}

	return null;
}

/**
 * The playable source for a post, or null for non-video pins.
 */
function ipin_post_video( int $post_id ): ?array {
	if ( ! get_post_meta( $post_id, '_ipin_is_video', true ) ) {
		return null;
	}
	return ipin_video_source( (string) get_post_meta( $post_id, '_ipin_video_embed_url', true ) );
}


/* -------------------------------------------------------
   PLAYER MARKUP (single post)
   ------------------------------------------------------- */
function ipin_video_player( int $post_id, array $video ): string {
	$title = get_the_title( $post_id );
	$vt    = 'view-transition-name: ipin-media-' . $post_id;

	if ( 'file' === $video['type'] ) {
		$poster = has_post_thumbnail( $post_id )
			? (string) wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'large' )
			: '';
		return sprintf(
			'<figure class="post-video post-video--file"><video controls playsinline preload="metadata"%1$s style="%2$s" aria-label="%3$s"><source src="%4$s" type="%5$s"></video></figure>',
			$poster ? ' poster="' . esc_url( $poster ) . '"' : '',
			esc_attr( $vt ),
			esc_attr( $title ),
			esc_url( $video['src'] ),
			esc_attr( $video['mime'] )
		);
	}

	return sprintf(
		'<figure class="post-video post-video--embed" style="%1$s"><iframe src="%2$s" title="%3$s" loading="lazy" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></figure>',
		esc_attr( $vt ),
		esc_url( $video['src'] ),
		esc_attr( $title )
	);
}


/* -------------------------------------------------------
   EDITOR BOX
   ------------------------------------------------------- */
function ipin_video_add_meta_box(): void {
	foreach ( [ 'post', 'ipin_article' ] as $type ) {
		add_meta_box(
			'ipin-video-pin',
			__( 'Video pin', 'ipin-modern' ),
			'ipin_video_render_meta_box',
			$type,
			'side'
		);
	}
}
add_action( 'add_meta_boxes', 'ipin_video_add_meta_box' );

function ipin_video_render_meta_box( WP_Post $post ): void {
	$url    = (string) get_post_meta( $post->ID, '_ipin_video_embed_url', true );
	$source = $url ? ipin_video_source( $url ) : null;

	wp_nonce_field( 'ipin_video_save', 'ipin_video_nonce' );
	?>
	<p>
		<label for="ipin_video_url"><?php esc_html_e( 'Video URL', 'ipin-modern' ); ?></label>
		<input type="url" id="ipin_video_url" name="ipin_video_url" class="widefat"
			value="<?php echo esc_attr( $url ); ?>"
			placeholder="https://menj.bio/…/clip.mp4">
	</p>
	<p class="description">
		<?php esc_html_e( 'A direct video file link (.mp4, .webm, .m4v, .mov, .ogv), or a YouTube or Vimeo link. The featured image becomes the poster. Leave blank for a photo pin.', 'ipin-modern' ); ?>
	</p>
	<?php if ( $url ) : ?>
	<p>
		<?php if ( $source ) : ?>
			<strong style="color:#007a53">&#10003; <?php echo esc_html( sprintf(
				/* translators: %s = source kind, e.g. "Video file" or "YouTube" */
				__( 'Playable: %s', 'ipin-modern' ),
				$source['label']
			) ); ?></strong>
		<?php else : ?>
			<strong style="color:#b32d2e">&#9888; <?php esc_html_e( 'Not playable. Paste the direct file link (ending in .mp4, .webm, .m4v, .mov or .ogv), not the page that shows the video.', 'ipin-modern' ); ?></strong>
		<?php endif; ?>
	</p>
	<?php endif;
}

function ipin_video_save_meta( int $post_id ): void {
	if ( ! isset( $_POST['ipin_video_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipin_video_nonce'] ) ), 'ipin_video_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url = esc_url_raw( trim( wp_unslash( (string) ( $_POST['ipin_video_url'] ?? '' ) ) ), [ 'http', 'https' ] );

	if ( '' === $url ) {
		delete_post_meta( $post_id, '_ipin_video_embed_url' );
		delete_post_meta( $post_id, '_ipin_is_video' );
		return;
	}

	update_post_meta( $post_id, '_ipin_video_embed_url', $url );
	update_post_meta( $post_id, '_ipin_is_video', ipin_video_source( $url ) ? 1 : 0 );
}
add_action( 'save_post', 'ipin_video_save_meta' );
