<?php
/**
 * iPin Modern — functions.php
 *
 * Theme bootstrap only. All logic is delegated to /inc/:
 *
 *   inc/enqueue.php        Asset enqueuing (CSS, JS, editor styles)
 *   inc/nav-walker.php     Accessible nav walker & menu filters
 *   inc/template-tags.php  Helper functions used in templates
 *   inc/admin-options.php  Tabbed admin settings page
 *   inc/customizer.php     WP Customizer integration
 *
 * PHP 8.x compatible. Bootstrap-free. No deprecated WP APIs.
 */

declare( strict_types = 1 );

// ── Safety check ────────────────────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   REQUIRE /inc/ FILES
   Load order matters: enqueue depends on template-tags
   for ipin_option(), so template-tags comes first.
   ------------------------------------------------------- */
$ipin_inc = get_template_directory() . '/inc/';

// ── Core ──────────────────────────────────────────────
require_once $ipin_inc . 'template-tags.php';   // helpers, comment callback, RSS filter
require_once $ipin_inc . 'nav-walker.php';       // Ipin_Nav_Walker + accessible nav filters

// ── Design features ───────────────────────────────────
require_once $ipin_inc . 'post-types.php';       // ipin_article CPT (sideblog)
require_once $ipin_inc . 'popular-posts.php';    // popular posts by comment count + widget

// ── Assets & UI ───────────────────────────────────────
require_once $ipin_inc . 'enqueue.php';          // all wp_enqueue_* calls
require_once $ipin_inc . 'video.php';            // video pins: source parser, player, editor box
require_once $ipin_inc . 'seo.php';              // meta description + JSON-LD structured data
require_once $ipin_inc . 'admin-options.php';    // tabbed admin settings page
require_once $ipin_inc . 'customizer.php';       // WP Customizer integration

unset( $ipin_inc );


/* -------------------------------------------------------
   THEME SETUP
   ------------------------------------------------------- */
function ipin_setup(): void {
	load_theme_textdomain( 'ipin', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [
		'comment-list',
		'comment-form',
		'search-form',
		'gallery',
		'caption',
		'style',
		'script',
	] );
	add_theme_support( 'custom-background', [
		'default-color' => 'F6F4F9',
	] );
	add_theme_support( 'custom-logo', [
		'height'      => 52,
		'flex-height' => true,
		'flex-width'  => true,
	] );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus( [
		'top_nav' => __( 'Top Navigation', 'ipin' ),
	] );

	// Block editor styles. 'editor-styles' support is what makes the block
	// editor load add_editor_style() files at all; tokens.css comes first so
	// editor-style.css can use the same colour variables as the front end.
	add_theme_support( 'editor-styles' );
	add_editor_style( [ 'assets/css/fonts.css', 'assets/css/tokens.css', 'assets/css/editor-style.css' ] );

	// Global content width (used by WP for oEmbed sizing etc.)
	$GLOBALS['content_width'] ??= 860;
}
add_action( 'after_setup_theme', 'ipin_setup' );


/* -------------------------------------------------------
   GRID PAGE SIZE
   Theme Options → General → "Posts per page" sets the batch
   size for every grid view (home, archives, search) so each
   infinite-scroll page is the same length.
   ------------------------------------------------------- */
function ipin_grid_page_size( \WP_Query $q ): void {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_home() || $q->is_archive() || $q->is_search() ) {
		$q->set( 'posts_per_page', ipin_sanitize_per_page( get_option( 'ipin_posts_per_page', 12 ) ) );
	}
}
add_action( 'pre_get_posts', 'ipin_grid_page_size' );


/* Sidebars removed by design — single posts and pages render full width. */
