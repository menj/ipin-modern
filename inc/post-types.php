<?php
/**
 * iPin Modern — Sideblog post type (ipin_article)
 *
 * Articles are longer pieces that sit beside the pin grid: their own
 * "Sideblog" menu in the admin, an archive at /articles/ and single pages
 * at /article/{slug}/. The theme registers the post type itself, so the
 * Sideblog works as soon as the theme is active. Articles stay in the
 * database if the theme is ever switched, and reappear when it comes back.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

function ipin_register_sideblog(): void {
	// The retired "iPin Sideblog" companion plugin registers the same post
	// type under the same name. If it is still active, let it; the settings
	// are identical.
	if ( post_type_exists( 'ipin_article' ) ) {
		return;
	}

	register_post_type( 'ipin_article', [
		'labels' => [
			'name'                  => __( 'Articles',               'ipin-modern' ),
			'singular_name'         => __( 'Article',                'ipin-modern' ),
			'add_new_item'          => __( 'Add New Article',        'ipin-modern' ),
			'edit_item'             => __( 'Edit Article',           'ipin-modern' ),
			'new_item'              => __( 'New Article',            'ipin-modern' ),
			'view_item'             => __( 'View Article',           'ipin-modern' ),
			'view_items'            => __( 'View Articles',          'ipin-modern' ),
			'search_items'          => __( 'Search Articles',        'ipin-modern' ),
			'not_found'             => __( 'No articles found.',     'ipin-modern' ),
			'not_found_in_trash'    => __( 'No articles found in Trash.', 'ipin-modern' ),
			'all_items'             => __( 'All Articles',           'ipin-modern' ),
			'archives'              => __( 'Article Archives',       'ipin-modern' ),
			'item_published'        => __( 'Article published.',     'ipin-modern' ),
			'item_updated'          => __( 'Article updated.',       'ipin-modern' ),
			'menu_name'             => __( 'Sideblog',               'ipin-modern' ),
		],
		'public'          => true,
		'rewrite'         => [ 'slug' => 'article', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive'     => 'articles',
		'menu_position'   => 5,
		'menu_icon'       => 'dashicons-text-page',
		'supports'        => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions' ],
		'show_in_rest'    => true,
	] );
}
add_action( 'init', 'ipin_register_sideblog' );

// New rewrite rules for /article/ and /articles/ on theme activation.
add_action( 'after_switch_theme', static function (): void {
	ipin_register_sideblog();
	flush_rewrite_rules();
} );


/* -------------------------------------------------------
   HAS ARTICLES
   True once at least one article is published. The header's
   fallback navigation uses it to add an "Articles" link, so
   the Sideblog is reachable before any menu is set up.
   ------------------------------------------------------- */
function ipin_has_articles(): bool {
	return post_type_exists( 'ipin_article' )
		&& (int) ( wp_count_posts( 'ipin_article' )->publish ?? 0 ) > 0;
}


/* -------------------------------------------------------
   RETIRED COMPANION PLUGIN
   5.0 pre-releases shipped the post type as a separate
   "iPin Sideblog" plugin. It is harmless but no longer
   needed; say so on the Plugins and iPin Settings screens.
   ------------------------------------------------------- */
function ipin_sideblog_plugin_notice(): void {
	if ( ! defined( 'IPIN_SIDEBLOG_LOADED' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, [ 'plugins', 'appearance_page_ipin-settings' ], true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		wp_kses(
			__( 'The Sideblog is now built into iPin Modern. You can deactivate and delete the <em>iPin Sideblog</em> plugin; your articles stay where they are.', 'ipin-modern' ),
			[ 'em' => [] ]
		)
	);
}
add_action( 'admin_notices', 'ipin_sideblog_plugin_notice' );
