<?php
/**
 * iPin Modern — REST API
 *
 * The lightbox data endpoint: GET /wp-json/ipin/v1/pin/{id}.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   LIGHTBOX DATA — REST
   GET /wp-json/ipin/v1/pin/{id} (or ?rest_route= on plain
   permalinks). Public, read-only and cacheable, unlike the
   admin-ajax POST it replaces. Text fields are plain text
   (ipin_plain()) and URLs are raw: the lightbox writes them
   with textContent and DOM properties, so HTML-escaping here
   would double-escape.
   ------------------------------------------------------- */
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
		'text'   => ipin_plain( ipin_comment_plain_text( $c->comment_content ) ),
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
		'hashtags'      => ipin_share_hashtags( $post_id ),
		'comments'      => $comments,
	];
}

function ipin_rest_pin( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
	$payload = ipin_lightbox_payload( (int) $request['id'] );
	if ( ! $payload ) {
		return new \WP_Error( 'ipin_pin_not_found', __( 'Pin not found.', 'ipin-modern' ), [ 'status' => 404 ] );
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
