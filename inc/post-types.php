<?php
/**
 * iPin Modern — Custom Post Types
 *
 * ipin_article  Sideblog: short editorial posts shown in the sidebar widget.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

function ipin_register_post_types(): void {
	register_post_type( 'ipin_article', [
		'labels' => [
			'name'          => __( 'Articles',        'ipin' ),
			'singular_name' => __( 'Article',         'ipin' ),
			'add_new_item'  => __( 'Add New Article', 'ipin' ),
			'menu_name'     => __( 'Sideblog',        'ipin' ),
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
add_action( 'init', 'ipin_register_post_types' );

add_action( 'after_switch_theme', static function (): void {
	ipin_register_post_types();
	flush_rewrite_rules();
} );
