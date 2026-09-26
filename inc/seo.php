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
 *
 * Structured data follows Google Search Central's guidelines for the
 * features it supports: Article, Breadcrumb, Organization, Profile page
 * and Video. The site's identity (a Person or an Organization, chosen in
 * Appearance → iPin Settings → Search) carries the sameAs links; the
 * WebSite, every Article and the author's ProfilePage point to it by @id.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   SEO PLUGIN DETECTION
   ------------------------------------------------------- */
function ipin_seo_plugin_active(): bool {
	return '' !== ipin_seo_plugin_name();
}

/** Name of the active SEO plugin the theme defers to, or ''. */
function ipin_seo_plugin_name(): string {
	return match ( true ) {
		defined( 'WPSEO_VERSION' )     => 'Yoast SEO',
		defined( 'RANK_MATH_VERSION' ) => 'Rank Math',
		defined( 'AIOSEO_VERSION' )    => 'All in One SEO',
		defined( 'SEOPRESS_VERSION' )  => 'SEOPress',
		default                        => '',
	};
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
   FEDIVERSE (MASTODON) HANDLE
   Accepts "@you@example.social", "you@example.social" or a
   profile URL "https://example.social/@you"; stores the
   canonical "@you@example.social", or '' if unrecognisable.
   ------------------------------------------------------- */
function ipin_sanitize_fediverse( mixed $value ): string {
	$value = trim( (string) $value );
	if ( preg_match( '#^https?://([a-z0-9.-]+\.[a-z]{2,})/@([a-z0-9_]{1,64})/?$#i', $value, $m ) ) {
		return '@' . $m[2] . '@' . strtolower( $m[1] );
	}
	if ( preg_match( '/^@?([a-z0-9_]{1,64})@([a-z0-9.-]+\.[a-z]{2,})$/i', $value, $m ) ) {
		return '@' . $m[1] . '@' . strtolower( $m[2] );
	}
	return '';
}

/** Profile URL for the configured handle, e.g. https://example.social/@you, or ''. */
function ipin_fediverse_profile_url(): string {
	$handle = ipin_sanitize_fediverse( get_option( 'ipin_fediverse_creator', '' ) );
	if ( '' === $handle ) {
		return '';
	}
	[ , $user, $host ] = explode( '@', $handle );
	return 'https://' . $host . '/@' . $user;
}

/**
 * fediverse:creator credits the author on Mastodon link previews;
 * rel="me" lets Mastodon verify this site on the profile.
 */
function ipin_fediverse_head(): void {
	$handle = ipin_sanitize_fediverse( get_option( 'ipin_fediverse_creator', '' ) );
	if ( '' === $handle ) {
		return;
	}
	echo '<meta name="fediverse:creator" content="' . esc_attr( $handle ) . '">' . "\n";
	echo '<link rel="me" href="' . esc_url( ipin_fediverse_profile_url() ) . '">' . "\n";
}
add_action( 'wp_head', 'ipin_fediverse_head', 2 );


/* -------------------------------------------------------
   STRUCTURED-DATA SETTINGS (Settings → Search)
   ------------------------------------------------------- */

/** JSON-LD on, and no SEO plugin printing its own. */
function ipin_schema_enabled(): bool {
	return (bool) (int) get_option( 'ipin_schema_enabled', 1 ) && ! ipin_seo_plugin_active();
}

/** Identity types the site can declare, slug => label. */
function ipin_schema_entities(): array {
	return [
		'person'       => __( 'A person', 'ipin-modern' ),
		'organization' => __( 'An organization', 'ipin-modern' ),
	];
}

function ipin_sanitize_schema_entity( mixed $value ): string {
	$value = sanitize_key( (string) $value );
	return array_key_exists( $value, ipin_schema_entities() ) ? $value : 'person';
}

/** The user the site represents when it is a person: the chosen one, else the first administrator. */
function ipin_schema_person_id(): int {
	$id = (int) get_option( 'ipin_schema_person', 0 );
	if ( $id && get_userdata( $id ) ) {
		return $id;
	}
	$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ] );
	return (int) ( $admins[0] ?? 0 );
}


/* -------------------------------------------------------
   SAME-AS PROFILE URLS
   The site identity's other homes on the web: every profile
   filled in under Settings → Social (unless switched off),
   the Mastodon handle, and the extra list under Settings →
   Search. Deduplicated, http(s) only.
   ------------------------------------------------------- */
function ipin_same_as_urls(): array {
	$urls = [];
	if ( (int) get_option( 'ipin_sameas_profiles', 1 ) ) {
		foreach ( array_keys( ipin_social_profiles() ) as $key ) {
			$urls[] = (string) get_option( $key, '' );
		}
		$urls[] = ipin_fediverse_profile_url();
	}
	$extra = preg_split( '/[\r\n]+/', (string) get_option( 'ipin_author_sameas', '' ) ) ?: [];
	$urls  = array_merge( $urls, $extra );
	$urls  = array_map( static fn( string $u ): string => esc_url_raw( trim( $u ), [ 'http', 'https' ] ), $urls );
	return array_values( array_unique( array_filter( $urls ) ) );
}


/* -------------------------------------------------------
   SITE IDENTITY NODE
   Person or Organization, @id home_url('/#identity').
   Organization follows Google's Organization guidelines
   (name, url, logo, sameAs); Person carries name, url,
   image, description and sameAs, as Google's Profile page
   guidelines recommend.
   ------------------------------------------------------- */
function ipin_identity_id(): string {
	return home_url( '/#identity' );
}

function ipin_identity_node(): array {
	$same_as = ipin_same_as_urls();

	if ( 'organization' === ipin_sanitize_schema_entity( get_option( 'ipin_schema_entity', 'person' ) ) ) {
		$name = trim( (string) get_option( 'ipin_schema_org_name', '' ) );
		$org  = [
			'@type' => 'Organization',
			'@id'   => ipin_identity_id(),
			'name'  => '' !== $name ? $name : ipin_plain( get_bloginfo( 'name', 'display' ) ),
			'url'   => home_url( '/' ),
		];
		// Logo: the Custom Logo, else the Site Icon (both at least 112px, as Google asks).
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
		if ( ! $logo && has_site_icon() ) {
			$logo = get_site_icon_url( 512 );
		}
		if ( $logo ) {
			$org['logo'] = esc_url_raw( $logo );
		}
		$tagline = ipin_plain( get_bloginfo( 'description', 'display' ) );
		if ( '' !== $tagline ) {
			$org['description'] = $tagline;
		}
		if ( $same_as ) {
			$org['sameAs'] = $same_as;
		}
		return $org;
	}

	$user_id = ipin_schema_person_id();
	$person  = [
		'@type' => 'Person',
		'@id'   => ipin_identity_id(),
		'name'  => $user_id ? ipin_plain( (string) get_the_author_meta( 'display_name', $user_id ) ) : ipin_plain( get_bloginfo( 'name', 'display' ) ),
		'url'   => $user_id ? get_author_posts_url( $user_id ) : home_url( '/' ),
	];
	if ( $user_id ) {
		$own = esc_url_raw( (string) get_the_author_meta( 'user_url', $user_id ), [ 'http', 'https' ] );
		if ( $own ) {
			array_unshift( $same_as, $own );
		}
		if ( get_option( 'show_avatars' ) ) {
			$person['image'] = esc_url_raw( (string) get_avatar_url( $user_id, [ 'size' => 256 ] ) );
		}
		$bio = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $user_id ), true ) );
		if ( $bio ) {
			$person['description'] = $bio;
		}
	}
	if ( $same_as ) {
		$person['sameAs'] = array_values( array_unique( $same_as ) );
	}
	return $person;
}


/* -------------------------------------------------------
   AUTHOR PERSON NODE
   The site's own person (when the site is a person) is the
   identity node itself, sameAs and all. Any other author
   gets a plain Person with only their own website as
   sameAs: the site's profiles are not theirs.
   ------------------------------------------------------- */
function ipin_person_node( int $author_id ): array {
	if ( 'person' === ipin_sanitize_schema_entity( get_option( 'ipin_schema_entity', 'person' ) )
		&& $author_id === ipin_schema_person_id() ) {
		return ipin_identity_node();
	}

	$person = [
		'@type' => 'Person',
		'@id'   => get_author_posts_url( $author_id ) . '#person',
		'name'  => ipin_plain( (string) get_the_author_meta( 'display_name', $author_id ) ),
		'url'   => get_author_posts_url( $author_id ),
	];
	$own = esc_url_raw( (string) get_the_author_meta( 'user_url', $author_id ), [ 'http', 'https' ] );
	if ( $own ) {
		$person['sameAs'] = [ $own ];
	}
	$bio = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', $author_id ), true ) );
	if ( $bio ) {
		$person['description'] = $bio;
	}
	return $person;
}


/* -------------------------------------------------------
   BREADCRUMB TRAIL
   One list for both the visible breadcrumb on single views
   (template-parts/breadcrumbs.php) and BreadcrumbList, so
   the markup always describes what the reader sees.
   Each item: [ name, url ] (url '' for the current page).
   ------------------------------------------------------- */
function ipin_breadcrumb_items( int $post_id ): array {
	$items = [ [ ipin_plain( get_bloginfo( 'name', 'display' ) ), home_url( '/' ) ] ];

	if ( 'ipin_article' === get_post_type( $post_id ) ) {
		$pto = get_post_type_object( 'ipin_article' );
		if ( $pto ) {
			$items[] = [ ipin_plain( (string) $pto->labels->name ), (string) get_post_type_archive_link( 'ipin_article' ) ];
		}
	} elseif ( 'post' === get_post_type( $post_id ) ) {
		$cats = get_the_category( $post_id );
		if ( $cats ) {
			$items[] = [ ipin_plain( $cats[0]->name ), (string) get_category_link( $cats[0] ) ];
		}
	}

	$items[] = [ ipin_plain( get_the_title( $post_id ) ), '' ];
	return $items;
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

	$desc = ipin_meta_description_text();
	if ( '' === $desc ) {
		return;
	}

	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'ipin_meta_description', 2 );


/**
 * The description text itself, also used for og:description
 * and twitter:description (inc/opengraph.php). '' when the
 * page has none.
 */
function ipin_meta_description_text(): string {
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

	if ( mb_strlen( $desc ) > 160 ) {
		$cut  = mb_substr( $desc, 0, 160 );
		$last = mb_strrpos( $cut, ' ' );
		$desc = ( $last ? mb_substr( $cut, 0, $last ) : $cut ) . '…';
	}

	return $desc;
}


/* -------------------------------------------------------
   STRUCTURED DATA (JSON-LD)
   WebSite on the home page; Article + BreadcrumbList on
   single posts, plus VideoObject when the pin is a video.
   Names are decoded to plain text, so the encoder also turns
   < > & into \u003C-style escapes (JSON_HEX_TAG | JSON_HEX_AMP):
   no title can close the <script> element or open an HTML
   comment inside it.
   ------------------------------------------------------- */
function ipin_structured_data(): void {
	if ( ! ipin_schema_enabled() ) {
		return;
	}

	$graph    = [];
	$identity = ipin_identity_node();
	$ref      = [ '@id' => ipin_identity_id() ];
	$need_id  = false;   // add the identity node when something points at it

	if ( is_front_page() || is_home() ) {
		$graph[] = [
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'name'      => ipin_plain( get_bloginfo( 'name', 'display' ) ),
			'url'       => home_url( '/' ),
			'publisher' => $ref,
		];
		$need_id = true;
	}

	if ( is_author() ) {
		$author_id = (int) get_queried_object_id();
		$person    = ipin_person_node( $author_id );
		if ( ! isset( $person['image'] ) && get_option( 'show_avatars' ) ) {
			$person['image'] = esc_url_raw( (string) get_avatar_url( $author_id, [ 'size' => 256 ] ) );
		}
		$graph[] = [
			'@type'      => 'ProfilePage',
			'url'        => get_author_posts_url( $author_id ),
			'mainEntity' => $person,
		];
		if ( ( $person['@id'] ?? '' ) === ipin_identity_id() ) {
			$need_id = false;   // already in the graph as mainEntity
			$identity = null;
		}
	}

	if ( is_singular( [ 'post', 'ipin_article' ] ) ) {
		$post_id   = get_queried_object_id();
		$author_id = (int) get_post_field( 'post_author', $post_id );
		$author    = ipin_person_node( $author_id );

		$article = [
			'@type'            => 'Article',
			'headline'         => ipin_plain( get_the_title( $post_id ) ),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'mainEntityOfPage' => get_permalink( $post_id ),
			'author'           => [ '@id' => $author['@id'] ],
			'publisher'        => $ref,
		];
		$graph[] = $author;
		$need_id = $author['@id'] !== ipin_identity_id();

		if ( has_post_thumbnail( $post_id ) ) {
			$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
			if ( $img ) {
				$article['image'] = [ $img[0] ];
			}
		}

		$graph[] = $article;

		// BreadcrumbList: the same trail the page shows above its title.
		$crumbs = [];
		foreach ( ipin_breadcrumb_items( $post_id ) as $i => [ $name, $url ] ) {
			$crumb = [ '@type' => 'ListItem', 'position' => $i + 1, 'name' => $name ];
			if ( '' !== $url ) {
				$crumb['item'] = $url;
			}
			$crumbs[] = $crumb;
		}
		$graph[] = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $crumbs,
		];

		// Video pin → VideoObject. Google requires name, thumbnailUrl and
		// uploadDate, so a video pin without a featured image gets none.
		$source = ipin_post_video( $post_id );
		if ( $source && ! empty( $article['image'] ) ) {
			$video = [
				'@type'        => 'VideoObject',
				'name'         => ipin_plain( get_the_title( $post_id ) ),
				'uploadDate'   => get_the_date( 'c', $post_id ),
				'thumbnailUrl' => $article['image'],
			];
			$video[ 'file' === $source['type'] ? 'contentUrl' : 'embedUrl' ] = esc_url_raw( $source['src'] );
			$desc = trim( wp_strip_all_tags( get_the_excerpt( $post_id ), true ) );
			if ( $desc ) {
				$video['description'] = $desc;
			}
			$graph[] = $video;
		}
	}

	if ( $need_id && $identity ) {
		array_unshift( $graph, $identity );
	}

	if ( ! $graph ) {
		return;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( [ '@context' => 'https://schema.org', '@graph' => $graph ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'ipin_structured_data', 3 );


/* -------------------------------------------------------
   ROBOTS — re-sorted copies of the grid
   The sort bar's ?popular= views list the same pins as the
   grid in another order. Google's starter guide asks that
   search-result-like duplicate pages stay out of the index,
   so they are noindex, follow (their pins stay reachable).
   WordPress already does the same for search results.
   ------------------------------------------------------- */
add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( ! ipin_seo_plugin_active() && ( is_home() || is_front_page() ) && '' !== sanitize_key( (string) ( $_GET['popular'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
} );
