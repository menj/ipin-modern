<?php
/**
 * iPin Modern — Core Web Vitals and head clean-up
 *
 * Nothing here changes visible output or stored data.
 *
 *  §1  Resource hints — preconnect / dns-prefetch for Gravatar (when
 *      avatars are on) and Pinterest (only when a Tag ID is set). The
 *      theme's fonts are self-hosted, so there is nothing else to warm up.
 *  §2  LCP preload — <link rel="preload" as="image" fetchpriority="high">
 *      with srcset/sizes for the featured image on singular views.
 *  §4  Head clean-up — emoji script and styles, generator tag, RSD,
 *      WLW manifest, shortlink, REST discovery link, oEmbed discovery.
 *  §5  No lazy-loading on the singular featured image (the LCP element).
 *  §7  Heartbeat off on the front end (the editor still has it).
 *  §8  No pingbacks from the site to itself.
 *
 * Restored in 5.1 from 4.5, less two sections: §6 (move jQuery to the
 * footer; 5.0 loads no jQuery) and §9 (AVIF/WebP upload transforms, which
 * change the image files WordPress stores, so they are left to a plugin).
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* ═══════════════════════════════════════════════════════
   §1  RESOURCE HINTS
   preconnect  — establish TCP+TLS to external origins early.
   dns-prefetch — cheaper fallback for browsers without preconnect.
   ═══════════════════════════════════════════════════════ */

function ipin_resource_hints( array $hints, string $relation_type ): array {

	if ( 'preconnect' === $relation_type ) {

		// Pinterest Tag CDN — only when a Tag ID is configured.
		if ( '' !== ipin_pinterest_tag_id() ) {
			$hints[] = [ 'href' => 'https://ct.pinterest.com', 'crossorigin' => 'anonymous' ];
			$hints[] = [ 'href' => 'https://s.pinimg.com',     'crossorigin' => 'anonymous' ];
		}

		// Gravatar — avatars on cards and in comments come from
		// secure.gravatar.com. Only when Settings → Discussion shows avatars.
		if ( get_option( 'show_avatars' ) ) {
			$hints[] = [ 'href' => 'https://secure.gravatar.com', 'crossorigin' => 'anonymous' ];
		}
	}

	if ( 'dns-prefetch' === $relation_type ) {
		// Pinterest CDN dns-prefetch fallback
		if ( '' !== ipin_pinterest_tag_id() ) {
			$hints[] = [ 'href' => '//ct.pinterest.com' ];
			$hints[] = [ 'href' => '//s.pinimg.com' ];
		}
		// Gravatar dns-prefetch fallback
		if ( get_option( 'show_avatars' ) ) {
			$hints[] = [ 'href' => '//secure.gravatar.com' ];
		}
	}

	return $hints;
}
add_filter( 'wp_resource_hints', 'ipin_resource_hints', 10, 2 );


/* ═══════════════════════════════════════════════════════
   §2  LCP IMAGE PRELOAD — singular pages only
   Outputs a <link rel="preload" as="image" fetchpriority="high">
   for the post thumbnail before the main stylesheet link tags,
   so the browser can start fetching the hero image in parallel
   with CSS rather than waiting for the HTML parser to reach the
   <img> tag deep in the document body.

   Uses wp_preload_resources (WP 6.1+) so WordPress handles
   deduplication and correct placement in <head>.
   ═══════════════════════════════════════════════════════ */

function ipin_preload_lcp_image( array $preload_resources ): array {
	if ( ! is_singular() ) {
		return $preload_resources;
	}

	$post_id       = get_the_ID();
	$attachment_id = get_post_thumbnail_id( $post_id );
	if ( ! $attachment_id ) {
		return $preload_resources;
	}

	// 'large' matches what single.php passes to the_post_thumbnail().
	// Video pins show a player instead, so they have no image to preload.
	if ( ipin_post_video( (int) $post_id ) ) {
		return $preload_resources;
	}
	$src = wp_get_attachment_image_src( $attachment_id, 'large' );
	if ( ! $src ) {
		return $preload_resources;
	}

	$resource = [
		'href'          => $src[0],
		'as'            => 'image',
		'fetchpriority' => 'high',   // §2+§3 combined: high-priority preload
	];

	// Responsive preload — browser fetches the correct breakpoint immediately
	$srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
	$sizes  = wp_get_attachment_image_sizes( $attachment_id, 'large' );
	if ( $srcset && $sizes ) {
		$resource['imagesrcset'] = $srcset;
		$resource['imagesizes']  = $sizes;
	}

	$preload_resources[] = $resource;

	return $preload_resources;
}
add_filter( 'wp_preload_resources', 'ipin_preload_lcp_image' );


/* ═══════════════════════════════════════════════════════
   §4  REMOVE UNNECESSARY WP HEAD OUTPUT
   WordPress outputs several <head> tags by default that
   serve no useful purpose on a modern image-board theme.
   ═══════════════════════════════════════════════════════ */

function ipin_remove_head_bloat(): void {
	// Emoji — inline JS (~8 KB), external wp-emoji-release.min.js, emoji.min.css.
	// The theme has no emoji-capable comment forms.
	remove_action( 'wp_head',         'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail',         'wp_staticize_emoji_for_email' );

	// WordPress version disclosure — <meta name="generator">
	remove_action( 'wp_head', 'wp_generator' );

	// Legacy XML-RPC discovery — RSD + Windows Live Writer (discontinued 2017)
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Miscellaneous unnecessary link tags
	remove_action( 'wp_head', 'wp_shortlink_wp_head',         10 );
	remove_action( 'wp_head', 'rest_output_link_wp_head',     10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links'    );
}
add_action( 'init', 'ipin_remove_head_bloat' );

// Disable the TinyMCE emoji plugin separately (fires in the block editor)
add_filter( 'tiny_mce_plugins', static function ( array $plugins ): array {
	return array_diff( $plugins, [ 'wpemoji' ] );
} );


/* ═══════════════════════════════════════════════════════
   §5  LAZY-LOAD OPT-OUT ON THE LCP IMAGE
   WordPress 5.5+ adds loading="lazy" to all images returned
   by wp_get_attachment_image() / the_post_thumbnail() by
   default. For the hero thumbnail (LCP candidate) this
   delays the fetch. We disable it for the_post_thumbnail
   context on singular pages so the hero image loads eagerly.
   ═══════════════════════════════════════════════════════ */

function ipin_disable_lcp_lazy( bool $default, string $tag_name, string $context ): bool {
	if ( is_singular() && 'the_post_thumbnail' === $context ) {
		return false;
	}
	return $default;
}
add_filter( 'wp_lazy_loading_enabled', 'ipin_disable_lcp_lazy', 10, 3 );


/* ═══════════════════════════════════════════════════════
   §7  HEARTBEAT API — FRONTEND DISABLE
   The Heartbeat API fires an AJAX POST to admin-ajax.php
   every 15–60 seconds on every logged-in frontend view.
   It exists to keep the block editor session alive and
   to prevent post-locking collisions during editing.

   On the frontend (non-admin pages) it serves no purpose.
   Disabling it there eliminates periodic wasted CPU wake-
   ups, idle network requests, and unnecessary server load
   on high-traffic sites.

   We disable it on the frontend only — the block editor
   (which needs it) runs on admin pages, not frontend ones.
   ═══════════════════════════════════════════════════════ */

// A child theme that needs Heartbeat on public pages can undo this with
// remove_action( 'init', 'ipin_disable_frontend_heartbeat' ) in after_setup_theme.
function ipin_disable_frontend_heartbeat(): void {
	if ( ! is_admin() ) {
		wp_deregister_script( 'heartbeat' );
	}
}
add_action( 'init', 'ipin_disable_frontend_heartbeat' );


/* ═══════════════════════════════════════════════════════
   §8  SELF-PING PREVENTION
   When you publish a post that contains a link to another
   post on your own domain, WordPress sends a pingback to
   itself. These self-pings serve no indexing or SEO
   purpose — they just add noise to your pingback queue
   and waste a server round-trip on every publish.

   The 'pre_ping' filter receives the list of pingback
   URLs and the list of URLs already pinged. We remove
   any URL that matches the site's own domain before
   the pingback request is sent.
   ═══════════════════════════════════════════════════════ */

add_action( 'pre_ping', static function ( array &$links ): void {
	$home = home_url();
	foreach ( $links as $key => $link ) {
		if ( str_starts_with( $link, $home ) ) {
			unset( $links[ $key ] );
		}
	}
} );
