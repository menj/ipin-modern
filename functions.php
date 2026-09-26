<?php
/**
 * iPin Modern — functions.php
 *
 * Theme bootstrap: loads the modules in /inc/, then sets up theme
 * supports. Where everything lives:
 *
 *   *.php (root)           Page templates WordPress picks by view
 *   template-parts/        Pieces the templates share: home hero, sort
 *                          bar, grid card, share bar
 *   inc/                   PHP modules, loaded below
 *   assets/css, js/        Stylesheets and scripts (see style.css for the map)
 *   assets/fonts, img/     Self-hosted fonts; favicon and social icons
 *   languages/             Translation template (ipin-modern.pot)
 *
 * PHP 8.x compatible. Bootstrap-free. No deprecated WP APIs.
 */

declare( strict_types = 1 );

// ── Safety check ────────────────────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   REQUIRE /inc/ FILES
   template-tags.php comes first: the others call its
   helpers (ipin_option(), ipin_plain(), ipin_icon()).
   ------------------------------------------------------- */
$ipin_inc = get_template_directory() . '/inc/';

// ── Core ──────────────────────────────────────────────
require_once $ipin_inc . 'template-tags.php';          // helpers the templates call, comment callback, 404 lines
require_once $ipin_inc . 'class-ipin-nav-walker.php';  // Ipin_Nav_Walker: menus with disclosure buttons
require_once $ipin_inc . 'enqueue.php';                // assets, scheme init, theme-color, favicon

// ── Content ───────────────────────────────────────────
require_once $ipin_inc . 'post-types.php';             // Sideblog: ipin_article post type
require_once $ipin_inc . 'popular-posts.php';          // sort bar ordering (?popular=)
require_once $ipin_inc . 'video.php';                  // video pins: source parser, player, editor box
require_once $ipin_inc . 'pin-source.php';             // "Pin source" editor box (_ipin_source_url)
require_once $ipin_inc . 'hidden-tags.php';            // hidden tags: kept out of loops, feeds, sitemap, tag lists
require_once $ipin_inc . 'markdown.php';               // Markdown for posts, articles and comments

// ── Output for machines ───────────────────────────────
require_once $ipin_inc . 'seo.php';                    // meta description + JSON-LD structured data
require_once $ipin_inc . 'opengraph.php';              // Open Graph + Twitter Card tags
require_once $ipin_inc . 'pinterest.php';              // Pinterest Tag, Rich Pin meta, lightbox Save switch
require_once $ipin_inc . 'performance.php';            // LCP preload, resource hints, head clean-up
require_once $ipin_inc . 'feed.php';                   // RSS: pin images and enclosures
require_once $ipin_inc . 'rest-api.php';               // lightbox data: GET /wp-json/ipin/v1/pin/{id}

// ── Admin ─────────────────────────────────────────────
require_once $ipin_inc . 'admin-options.php';          // tabbed settings page
require_once $ipin_inc . 'customizer.php';             // Customizer integration

unset( $ipin_inc );


/* -------------------------------------------------------
   THEME SETUP
   ------------------------------------------------------- */
function ipin_setup(): void {
	load_theme_textdomain( 'ipin-modern', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	// Grid card rendition: 2x the widest card setting (400px), any height.
	add_image_size( 'ipin-card', 800, 0, false );
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
		'top_nav' => __( 'Top Navigation', 'ipin-modern' ),
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
