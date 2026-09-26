<?php
/**
 * iPin Modern — Open Graph and Twitter Card tags
 *
 * Link previews for Facebook, Pinterest, WhatsApp, Telegram, Mastodon and X.
 * Output on singular views and the home page only, where the page has one
 * clear subject.
 *
 * Tags: og:site_name, og:locale, og:type, og:url, og:title, og:description,
 * og:image (+ secure_url, width, height, alt, type), article:published_time,
 * article:modified_time, article:section, article:tag, twitter:card,
 * twitter:title, twitter:description, twitter:site, twitter:creator,
 * twitter:image (+ alt).
 *
 * Stands down with the rest of inc/seo.php when an SEO plugin is active
 * (ipin_seo_plugin_active()), since those plugins print their own.
 *
 * Restored in 5.1 from 4.5. The description now comes from the same
 * helper as the description meta tag (ipin_meta_description_text()), and
 * hidden tags never reach article:tag because get_the_tags() is filtered
 * by inc/hidden-tags.php.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   MAIN OUTPUT
   ------------------------------------------------------- */
function ipin_output_og_tags(): void {
	if ( ipin_seo_plugin_active() ) {
		return;
	}
	if ( ! is_singular() && ! is_home() && ! is_front_page() ) {
		return;
	}

	$singular = is_singular();
	$title    = $singular
		? wp_strip_all_tags( get_the_title() )
		: get_bloginfo( 'name' );
	$desc     = ipin_meta_description_text();
	if ( '' === $desc ) {
		$desc = get_bloginfo( 'description' );
	}

	// Image: featured image, else the first attached image.
	$img_id = 0;
	$img    = [ '', 0, 0 ];
	if ( $singular ) {
		$img_id = (int) get_post_thumbnail_id();
		if ( ! $img_id ) {
			$attached = get_children( [
				'post_parent'    => get_the_ID(),
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => 1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			] );
			$img_id = $attached ? (int) reset( $attached )->ID : 0;
		}
		if ( $img_id ) {
			$src = wp_get_attachment_image_src( $img_id, 'large' );
			if ( $src ) {
				$img = [ (string) $src[0], (int) $src[1], (int) $src[2] ];
			}
		}
	}
	[ $img_url, $img_w, $img_h ] = $img;

	echo "\n<!-- Open Graph / Twitter Card -->\n";

	ipin_og_tag( 'og:site_name',   get_bloginfo( 'name' ) );
	ipin_og_tag( 'og:locale',      str_replace( '-', '_', get_locale() ) );
	ipin_og_tag( 'og:type',        $singular ? 'article' : 'website' );
	ipin_og_tag( 'og:url',         $singular ? (string) get_permalink() : home_url( '/' ) );
	ipin_og_tag( 'og:title',       $title );
	ipin_og_tag( 'og:description', $desc );

	if ( $img_url ) {
		ipin_og_tag( 'og:image', $img_url );
		if ( str_starts_with( $img_url, 'https://' ) ) {
			ipin_og_tag( 'og:image:secure_url', $img_url );
		}
		if ( $img_w ) ipin_og_tag( 'og:image:width',  (string) $img_w );
		if ( $img_h ) ipin_og_tag( 'og:image:height', (string) $img_h );
		ipin_og_tag( 'og:image:alt',  $title );
		ipin_og_tag( 'og:image:type', (string) get_post_mime_type( $img_id ) );
	}

	if ( $singular ) {
		ipin_og_tag( 'article:published_time', (string) get_the_date( 'c' ) );
		ipin_og_tag( 'article:modified_time',  (string) get_the_modified_date( 'c' ) );

		$cats = get_the_category();
		if ( $cats ) {
			ipin_og_tag( 'article:section', $cats[0]->name );
		}
		$tags = get_the_tags();
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $t ) {
				ipin_og_tag( 'article:tag', $t->name );
			}
		}
	}

	ipin_tw_tag( 'twitter:card',        $img_url ? 'summary_large_image' : 'summary' );
	ipin_tw_tag( 'twitter:title',       $title );
	ipin_tw_tag( 'twitter:description', $desc );

	// Site handle from Settings → Social → Twitter / X.
	$site_x = (string) get_option( 'ipin_twitter_url', '' );
	if ( $site_x && preg_match( '#(?:twitter\.com|x\.com)/(\w+)#i', $site_x, $m ) ) {
		ipin_tw_tag( 'twitter:site', '@' . $m[1] );
	}
	// Author handle from the author's profile, when a plugin or the site adds that field.
	if ( $singular ) {
		$author_x = (string) get_the_author_meta( 'twitter' );
		if ( $author_x && preg_match( '#(?:twitter\.com|x\.com)/(\w+)#i', $author_x, $m ) ) {
			ipin_tw_tag( 'twitter:creator', '@' . $m[1] );
		}
	}

	if ( $img_url ) {
		ipin_tw_tag( 'twitter:image',     $img_url );
		ipin_tw_tag( 'twitter:image:alt', $title );
	}

	echo "<!-- / Open Graph -->\n\n";
}
add_action( 'wp_head', 'ipin_output_og_tags', 1 );


/* -------------------------------------------------------
   TAG HELPERS — an empty value prints nothing
   ------------------------------------------------------- */
function ipin_og_tag( string $property, string $content ): void {
	if ( '' === $content ) return;
	printf( "<meta property=\"%s\" content=\"%s\">\n", esc_attr( $property ), esc_attr( $content ) );
}

function ipin_tw_tag( string $name, string $content ): void {
	if ( '' === $content ) return;
	printf( "<meta name=\"%s\" content=\"%s\">\n", esc_attr( $name ), esc_attr( $content ) );
}
