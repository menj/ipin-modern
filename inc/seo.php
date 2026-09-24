<?php
/**
 * iPin Modern — Search & Structured Data
 *
 * On-page items from Google's SEO Starter Guide that belong in
 * the theme (unique per-page "description" meta tags — titles
 * are already covered by add_theme_support( 'title-tag' )),
 * plus the structured-data types from Google's supported list
 * that fit this theme: WebSite, Article, BreadcrumbList and
 * VideoObject for video pins. Emitted as JSON-LD, Google's
 * recommended format.
 *
 * Every output here stands down when a dedicated SEO plugin is
 * active, so the theme never duplicates its tags.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   SEO PLUGIN DETECTION
   ------------------------------------------------------- */
function ipin_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' )      // Yoast SEO
		|| defined( 'RANK_MATH_VERSION' )  // Rank Math
		|| defined( 'AIOSEO_VERSION' )     // All in One SEO
		|| defined( 'SEOPRESS_VERSION' );  // SEOPress
}


/* -------------------------------------------------------
   URL-LIST SANITIZER
   For the "sameAs" setting: one URL per line, each run
   through esc_url_raw, blanks dropped.
   ------------------------------------------------------- */
function ipin_sanitize_url_list( mixed $value ): string {
	$lines = preg_split( '/[\r\n]+/', (string) $value ) ?: [];
	$urls  = array_filter( array_map( 'esc_url_raw', array_map( 'trim', $lines ) ) );
	return implode( "\n", $urls );
}


/* -------------------------------------------------------
   SAME-AS PROFILE URLS
   The configured nav social profiles plus the "also me"
   list from Settings → Social, deduplicated.
   ------------------------------------------------------- */
function ipin_same_as_urls(): array {
	$urls = [
		(string) get_option( 'ipin_twitter_url', '' ),
		(string) get_option( 'ipin_facebook_url', '' ),
		(string) get_option( 'ipin_instagram_url', '' ),
	];
	$extra = preg_split( '/[\r\n]+/', (string) get_option( 'ipin_author_sameas', '' ) ) ?: [];
	$urls  = array_merge( $urls, $extra );
	$urls  = array_filter( array_map( 'esc_url_raw', array_map( 'trim', $urls ) ) );
	return array_values( array_unique( $urls ) );
}


/* -------------------------------------------------------
   AUTHOR PERSON NODE
   Referenced from Article.author and the author archive's
   ProfilePage; carries the sameAs links that tie this site
   to the author's other properties.
   ------------------------------------------------------- */
function ipin_person_node( int $author_id ): array {
	$person = [
		'@type' => 'Person',
		'@id'   => get_author_posts_url( $author_id ) . '#person',
		'name'  => get_the_author_meta( 'display_name', $author_id ),
		'url'   => get_author_posts_url( $author_id ),
	];

	$site_url = (string) get_the_author_meta( 'user_url', $author_id );
	$same_as  = ipin_same_as_urls();
	if ( $site_url ) {
		array_unshift( $same_as, esc_url_raw( $site_url ) );
	}
	if ( $same_as ) {
		$person['sameAs'] = array_values( array_unique( $same_as ) );
	}

	$bio = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $author_id ), true ) );
	if ( $bio ) {
		$person['description'] = $bio;
	}

	return $person;
}


/* -------------------------------------------------------
   DESCRIPTION META TAG
   Unique per page: excerpt on singular, term description
   on archives, author bio on author pages, tagline on the
   home page. Trimmed to snippet length at a word boundary.
   ------------------------------------------------------- */
function ipin_meta_description(): void {
	if ( ipin_seo_plugin_active() ) {
		return;
	}

	$desc = '';

	if ( is_singular() ) {
		$desc = get_the_excerpt();
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} elseif ( is_author() ) {
		$desc = get_the_author_meta( 'description', get_queried_object_id() );
	} elseif ( is_home() || is_front_page() ) {
		$desc = get_bloginfo( 'description', 'display' );
	}

	$desc = trim( wp_strip_all_tags( (string) $desc, true ) );
	if ( '' === $desc ) {
		return;
	}

	if ( mb_strlen( $desc ) > 160 ) {
		$cut  = mb_substr( $desc, 0, 160 );
		$last = mb_strrpos( $cut, ' ' );
		$desc = ( $last ? mb_substr( $cut, 0, $last ) : $cut ) . '…';
	}

	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'ipin_meta_description', 2 );


/* -------------------------------------------------------
   STRUCTURED DATA (JSON-LD)
   WebSite on the home page; Article + BreadcrumbList on
   single posts, plus VideoObject when the pin is a video.
   wp_json_encode() escapes forward slashes, so encoded
   content can never break out of the <script> element.
   ------------------------------------------------------- */
function ipin_structured_data(): void {
	if ( ipin_seo_plugin_active() ) {
		return;
	}

	$graph = [];

	if ( is_front_page() || is_home() ) {
		$website = [
			'@type' => 'WebSite',
			'name'  => get_bloginfo( 'name', 'display' ),
			'url'   => home_url( '/' ),
		];
		$same_as = ipin_same_as_urls();
		if ( $same_as ) {
			$website['sameAs'] = $same_as;
		}
		$graph[] = $website;
	}

	if ( is_author() ) {
		$author_id = (int) get_queried_object_id();
		$graph[]   = [
			'@type'      => 'ProfilePage',
			'mainEntity' => ipin_person_node( $author_id ),
		];
	}

	if ( is_singular( [ 'post', 'ipin_article' ] ) ) {
		$post_id   = get_queried_object_id();
		$author_id = (int) get_post_field( 'post_author', $post_id );

		$article = [
			'@type'            => 'Article',
			'headline'         => get_the_title( $post_id ),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'mainEntityOfPage' => get_permalink( $post_id ),
			'author'           => [ '@id' => get_author_posts_url( $author_id ) . '#person' ],
		];
		$graph[] = ipin_person_node( $author_id );

		if ( has_post_thumbnail( $post_id ) ) {
			$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
			if ( $img ) {
				$article['image'] = [ $img[0] ];
			}
		}

		$graph[] = $article;

		// Breadcrumb: Home → primary category → this post.
		$crumbs = [
			[
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => get_bloginfo( 'name', 'display' ),
				'item'     => home_url( '/' ),
			],
		];
		$cats = get_the_category( $post_id );
		if ( $cats ) {
			$crumbs[] = [
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $cats[0]->name,
				'item'     => get_category_link( $cats[0] ),
			];
		}
		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => count( $crumbs ) + 1,
			'name'     => get_the_title( $post_id ),
		];
		$graph[] = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $crumbs,
		];

		// Video pin → VideoObject (contentUrl for files, embedUrl for players).
		$source = ipin_post_video( $post_id );
		if ( $source ) {
			$video = [
				'@type'      => 'VideoObject',
				'name'       => get_the_title( $post_id ),
				'uploadDate' => get_the_date( 'c', $post_id ),
			];
			$video[ 'file' === $source['type'] ? 'contentUrl' : 'embedUrl' ] = esc_url_raw( $source['src'] );
			if ( ! empty( $article['image'] ) ) {
				$video['thumbnailUrl'] = $article['image'];
			}
			$desc = trim( wp_strip_all_tags( get_the_excerpt( $post_id ), true ) );
			if ( $desc ) {
				$video['description'] = $desc;
			}
			$graph[] = $video;
		}
	}

	if ( ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( [ '@context' => 'https://schema.org', '@graph' => $graph ] )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'ipin_structured_data', 3 );
