<?php
/**
 * Plugin Name:       iPin Sideblog
 * Description:       Registers the "Articles" (ipin_article) post type used by the iPin Modern theme, so your articles stay available if you ever switch themes.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            MENJ
 * Author URI:        https://menj.org
 * License:           GPL-2.0-or-later
 * Text Domain:       ipin-modern
 *
 * The iPin Modern theme loads this same file itself when the plugin is not
 * active, so content never disappears; installing the plugin just moves the
 * registration out of the theme. Install by copying this folder to
 * wp-content/plugins/ and activating "iPin Sideblog".
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ipin_sideblog_register' ) ) {

	define( 'IPIN_SIDEBLOG_LOADED', true );

	function ipin_sideblog_register(): void {
		register_post_type( 'ipin_article', [
			'labels' => [
				'name'          => __( 'Articles',        'ipin-modern' ),
				'singular_name' => __( 'Article',         'ipin-modern' ),
				'add_new_item'  => __( 'Add New Article', 'ipin-modern' ),
				'menu_name'     => __( 'Sideblog',        'ipin-modern' ),
			],
			'public'          => true,
			'rewrite'         => [ 'slug' => 'article', 'with_front' => false ],
			'capability_type' => 'post',
			'has_archive'     => 'articles',
			'menu_icon'       => 'dashicons-text-page',
			'supports'        => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ],
			'show_in_rest'    => true,
		] );
	}
	add_action( 'init', 'ipin_sideblog_register' );

	// Only meaningful when running as a plugin (the theme flushes on switch).
	if ( defined( 'WP_PLUGIN_DIR' ) && str_starts_with( wp_normalize_path( __FILE__ ), wp_normalize_path( WP_PLUGIN_DIR ) ) ) {
		register_activation_hook( __FILE__, static function (): void {
			ipin_sideblog_register();
			flush_rewrite_rules();
		} );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}
}
