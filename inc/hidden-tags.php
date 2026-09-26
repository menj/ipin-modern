<?php
/**
 * iPin Modern — Hidden Tag Visibility System
 *
 * Allows administrators to mark specific post tags as "hidden".
 * Posts assigned any hidden tag are:
 *   - Excluded from all public-facing loops, archives, feeds, sitemaps
 *   - Excluded from front-end tag output (tag lists, tag clouds, widgets)
 *   - Still accessible via their direct single-post URL (is_singular)
 *   - Still accessible within the matching hidden tag archive (is_tag)
 *
 * Architecture is intentionally modular: add future post-type support
 * or taxonomy-based rules by extending the helper and filter functions
 * without touching the calling code.
 *
 * Scope: standard 'post' post type only (see extensibility notes inline).
 *
 * Restored in 5.1 from 4.5. The option is declared with the other theme
 * settings in ipin_settings_schema() (inc/admin-options.php) and edited on
 * Appearance → iPin Settings → Visibility.
 *
 * PHP 8.0+. No external dependencies.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* =======================================================
   HELPERS
   ======================================================= */

/**
 * Return validated hidden tag IDs.
 *
 * Reads the stored option, validates each ID against the live
 * post_tag taxonomy (deleted/renamed tags are silently ignored),
 * and caches the result for the request lifetime.
 *
 * @return int[] Validated term IDs.
 */
function ipin_get_hidden_tag_ids(): array {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$raw = get_option( 'hidden_tags', [] );

	if ( ! is_array( $raw ) || empty( $raw ) ) {
		$cached = [];
		return $cached;
	}

	// term_exists() validates against live taxonomy — handles deleted/renamed tags.
	$valid = [];
	foreach ( $raw as $id ) {
		$id = (int) $id;
		if ( $id > 0 && term_exists( $id, 'post_tag' ) ) {
			$valid[] = $id;
		}
	}

	$cached = array_values( $valid );
	return $cached;
}

/**
 * Sanitize the hidden_tags option value.
 *
 * Accepts a JSON-encoded string (from JS) or an array.
 * Validates every entry against the live post_tag taxonomy.
 * Called on both settings registration and AJAX save.
 *
 * @param  mixed $value Raw input.
 * @return int[] Clean, validated array of term IDs.
 */
function ipin_sanitize_hidden_tags( mixed $value ): array {
	// JS multi-select sends JSON; handle both JSON string and native array.
	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = is_array( $decoded ) ? $decoded : array_filter( explode( ',', $value ) );
	}

	if ( ! is_array( $value ) ) {
		return [];
	}

	$clean = [];
	foreach ( $value as $id ) {
		$id = (int) $id;
		if ( $id > 0 && term_exists( $id, 'post_tag' ) ) {
			$clean[] = $id;
		}
	}

	return array_values( array_unique( $clean ) );
}

/**
 * Append a NOT IN tax_query clause to a WP_Query instance.
 *
 * Merges with any existing tax_query so other filters are not
 * clobbered. Intentionally separate from the pre_get_posts
 * callback so it can be reused by custom query helpers.
 *
 * Extensibility note: to apply to additional post types, pass
 * the $post_type parameter and gate the clause here.
 *
 * @param WP_Query $q      Query to modify.
 * @param int[]    $hidden Validated hidden tag IDs.
 */
function ipin_apply_hidden_tag_exclusion( \WP_Query $q, array $hidden ): void {
	$existing = $q->get( 'tax_query' );
	if ( ! is_array( $existing ) ) {
		$existing = [];
	}

	// Set explicit relation so WP_Query applies ALL clauses (AND is the default
	// but being explicit avoids ambiguity when other theme code also modifies tax_query).
	if ( ! isset( $existing['relation'] ) ) {
		$existing['relation'] = 'AND';
	}

	$existing[] = [
		'taxonomy' => 'post_tag',
		'field'    => 'term_id',
		'terms'    => $hidden,
		'operator' => 'NOT IN',
	];

	$q->set( 'tax_query', $existing );
}


/* =======================================================
   PUBLIC QUERY FILTERING — pre_get_posts
   ======================================================= */

add_action( 'pre_get_posts', static function ( \WP_Query $q ): void {

	// ── Skip wp-admin screens (post editor, list tables, etc.)
	// Feeds and AJAX requests pass through admin URLs but deliver
	// public data, so we continue filtering for those.
	$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
	if ( is_admin() && ! $q->is_feed() && ! $is_ajax ) {
		return;
	}

	// ── Skip in wp-admin preview contexts (editors / administrators
	// must see hidden-tag posts normally in admin previews).
	if ( is_admin() && isset( $_GET['preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check
		return;
	}

	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return;
	}

	// ── ALLOW: direct single post URL.
	// $q->is_singular() is set early in the query lifecycle.
	if ( $q->is_singular( 'post' ) ) {
		return;
	}

	// ── ALLOW: the hidden tag archive itself.
	// During pre_get_posts, the queried object is not yet set;
	// we read from query vars instead.
	if ( $q->is_tag() ) {
		// Tag can be addressed by slug (?tag=foo) or numeric ID.
		$tag_slug = (string) $q->get( 'tag' );
		$tag_id   = (int) $q->get( 'tag_id' );

		if ( $tag_id && in_array( $tag_id, $hidden, true ) ) {
			return; // hidden tag archive by ID
		}

		if ( $tag_slug ) {
			$term = get_term_by( 'slug', $tag_slug, 'post_tag' );
			if ( $term instanceof \WP_Term && in_array( (int) $term->term_id, $hidden, true ) ) {
				return; // hidden tag archive by slug
			}
		}
	}

	// ── EXCLUDE from all other queries.
	ipin_apply_hidden_tag_exclusion( $q, $hidden );
} );


/* =======================================================
   TAG SUPPRESSION — front-end output
   Strips hidden tags from the_tags(), post meta, tag links,
   and any call that goes through get_the_terms().
   Exception: the matching hidden tag archive shows the tag.
   ======================================================= */

add_filter( 'get_the_terms', static function ( mixed $terms, int $post_id, string $taxonomy ): mixed {
	if ( 'post_tag' !== $taxonomy ) {
		return $terms;
	}

	// Do not suppress in wp-admin (post editor, quick-edit, etc.)
	if ( is_admin() ) {
		return $terms;
	}

	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return $terms;
	}

	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return $terms;
	}

	// Allow on the matching hidden tag archive itself.
	if ( is_tag() ) {
		$obj = get_queried_object();
		if ( $obj instanceof \WP_Term && in_array( (int) $obj->term_id, $hidden, true ) ) {
			return $terms;
		}
	}

	return array_values(
		array_filter(
			$terms,
			static fn( mixed $t ) => $t instanceof \WP_Term && ! in_array( (int) $t->term_id, $hidden, true )
		)
	);
}, 10, 3 );


/* =======================================================
   TAG CLOUD SUPPRESSION
   Removes hidden tags from wp_tag_cloud() and the default
   WP Tag Cloud widget.
   ======================================================= */

add_filter( 'wp_generate_tag_cloud_data', static function ( array $tags_data ): array {
	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return $tags_data;
	}

	// Allow on the matching hidden tag archive itself.
	if ( is_tag() ) {
		$obj = get_queried_object();
		if ( $obj instanceof \WP_Term && in_array( (int) $obj->term_id, $hidden, true ) ) {
			return $tags_data;
		}
	}

	return array_values(
		array_filter(
			$tags_data,
			static fn( array $tag ) => ! in_array( (int) $tag['id'], $hidden, true )
		)
	);
} );


/* =======================================================
   SITEMAP EXCLUSION (WordPress core sitemap — WP 5.5+)
   Yoast SEO and Rank Math both honour pre_get_posts, so the
   main filter above already covers their sitemaps. This hook
   is a belt-and-suspenders guard for the built-in sitemap.
   ======================================================= */

add_filter( 'wp_sitemaps_posts_query_args', static function ( array $args, string $post_type ): array {
	if ( 'post' !== $post_type ) {
		return $args;
	}

	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return $args;
	}

	$existing   = $args['tax_query'] ?? [];
	if ( ! isset( $existing['relation'] ) ) {
		$existing['relation'] = 'AND';
	}
	$existing[] = [
		'taxonomy' => 'post_tag',
		'field'    => 'term_id',
		'terms'    => $hidden,
		'operator' => 'NOT IN',
	];

	$args['tax_query'] = $existing;
	return $args;
}, 10, 2 );


/* =======================================================
   CUSTOM THEME LOOP ABSTRACTION HOOK
   Any custom WP_Query built by theme code (not through
   pre_get_posts) should apply_filters('ipin_query_args', $args)
   before passing args to WP_Query. This filter enforces
   the hidden-tag exclusion on those calls automatically.
   ======================================================= */

add_filter( 'ipin_query_args', static function ( array $args ): array {
	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return $args;
	}

	$existing   = $args['tax_query'] ?? [];
	if ( ! isset( $existing['relation'] ) ) {
		$existing['relation'] = 'AND';
	}
	$existing[] = [
		'taxonomy' => 'post_tag',
		'field'    => 'term_id',
		'terms'    => $hidden,
		'operator' => 'NOT IN',
	];

	$args['tax_query'] = $existing;
	return $args;
} );


/* =======================================================
   ADJACENT POST NAVIGATION
   previous_post_link() / next_post_link() run their own SQL
   through get_adjacent_post() and never see pre_get_posts.
   These filters add the same exclusion to that SQL, so the
   prev/next links on a single post skip hidden-tag posts.
   ======================================================= */

function ipin_hidden_tags_adjacent_where( string $where, bool $in_same_term, mixed $excluded_terms, string $taxonomy, \WP_Post $post ): string {
	if ( 'post' !== $post->post_type ) {
		return $where;
	}

	$hidden = ipin_get_hidden_tag_ids();
	if ( empty( $hidden ) ) {
		return $where;
	}

	global $wpdb;
	$ids = implode( ',', array_map( 'intval', $hidden ) ); // validated term IDs, cast again

	return $where . " AND p.ID NOT IN (
		SELECT tr.object_id FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE tt.taxonomy = 'post_tag' AND tt.term_id IN ({$ids})
	)";
}
add_filter( 'get_previous_post_where', 'ipin_hidden_tags_adjacent_where', 10, 5 );
add_filter( 'get_next_post_where',     'ipin_hidden_tags_adjacent_where', 10, 5 );


/* =======================================================
   ROBOTS — hidden tag archives are noindex
   The archive stays reachable (it is the one listing that
   still shows these posts) but is kept out of search indexes.
   ======================================================= */

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( ! is_tag() ) {
		return $robots;
	}

	$term = get_queried_object();
	if ( $term instanceof \WP_Term && in_array( (int) $term->term_id, ipin_get_hidden_tag_ids(), true ) ) {
		$robots['noindex'] = true;
		unset( $robots['index'] );
	}

	return $robots;
} );
