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
require_once $ipin_inc . 'ads.php';              // ad slot definitions & render helpers

// ── Assets & UI ───────────────────────────────────────
require_once $ipin_inc . 'enqueue.php';          // all wp_enqueue_* calls
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

	// Block editor styles — registered here (after_setup_theme) so the block
	// editor picks them up. Calling add_editor_style() in wp_enqueue_scripts
	// only affects the frontend and has no effect in the editor.
	add_editor_style( 'assets/css/editor-style.css' );

	// Global content width (used by WP for oEmbed sizing etc.)
	$GLOBALS['content_width'] ??= 860;
}
add_action( 'after_setup_theme', 'ipin_setup' );


/* -------------------------------------------------------
   WIDGET AREAS
   ------------------------------------------------------- */
function ipin_widgets_init(): void {
	$shared = [
		'before_widget' => '<div class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4>',
		'after_title'   => '</h4>',
	];

	register_sidebar( array_merge( $shared, [
		'name'        => __( 'Right Sidebar', 'ipin' ),
		'id'          => 'sidebar-right',
		'description' => __( 'Widgets in the right column of single posts and pages.', 'ipin' ),
	] ) );

	register_sidebar( array_merge( $shared, [
		'name'        => __( 'Left Sidebar', 'ipin' ),
		'id'          => 'sidebar-left',
		'description' => __( 'Widgets in the left column (left-sidebar page template).', 'ipin' ),
	] ) );
}
add_action( 'widgets_init', 'ipin_widgets_init' );
