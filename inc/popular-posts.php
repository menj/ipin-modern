<?php
/**
 * iPin Modern — Popular Sort
 *
 * The grid's sort bar (Latest / Last 7 days / This month / All time)
 * re-orders the main query by comment count via ?popular=. The links
 * are built on the blog index URL, so they keep working when a static
 * front page is set and the grid lives on the Posts page.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   GRID BASE URL
   The page that shows the post grid: the Posts page when a
   static front page is configured, otherwise the home URL.
   ------------------------------------------------------- */
function ipin_grid_base_url(): string {
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		if ( $posts_page ) {
			return (string) get_permalink( $posts_page );
		}
	}
	return home_url( '/' );
}


/* -------------------------------------------------------
   SORT BAR URL
   $period: '' (latest) | '7days' | '30days' | 'all'
   ------------------------------------------------------- */
function ipin_popular_sort_url( string $period = '' ): string {
	$base = ipin_grid_base_url();
	return '' === $period ? $base : add_query_arg( 'popular', $period, $base );
}


/* -------------------------------------------------------
   PRE_GET_POSTS: honour ?popular= on the grid page
   ------------------------------------------------------- */
add_action( 'pre_get_posts', static function ( \WP_Query $q ): void {
	if ( ! $q->is_main_query() || is_admin() ) return;
	if ( ! $q->is_home() && ! $q->is_front_page() ) return;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort parameter
	$period = sanitize_key( $_GET['popular'] ?? '' );
	if ( ! in_array( $period, [ '7days', '30days', 'all' ], true ) ) return;

	$q->set( 'orderby', 'comment_count' );
	$q->set( 'order',   'DESC' );

	if ( $period !== 'all' ) {
		$days = ( $period === '7days' ) ? 7 : 30;
		$q->set( 'date_query', [ [
			'after'     => gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
			'inclusive' => true,
		] ] );
	}
} );
