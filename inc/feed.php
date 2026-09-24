<?php
/**
 * iPin Modern — RSS Feed
 *
 * Adds each pin's image to feed items, and an <enclosure> with
 * the video file or featured image so feed readers show media.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


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
