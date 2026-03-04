<?php
/**
 * iPin Pro — Ad Slot Manager
 *
 * Slots:
 *   ipin_ad_header          — top of every page, full-width banner
 *   ipin_ad_grid_{1-5}      — injected into masonry grid (positions 5,10,15,20,25)
 *   ipin_ad_above_photo     — single post, above featured image
 *   ipin_ad_below_photo     — single post, below featured image
 *
 * Each slot holds raw HTML (paste AdSense/any code) or is left blank.
 * Blank slots are silently skipped — no empty divs.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   SLOT DEFINITIONS
   ------------------------------------------------------- */
function ipin_ad_slots(): array {
	return [
		'ipin_ad_header'     => __( 'Top Header Banner (full width)',        'ipin' ),
		'ipin_ad_grid_1'     => __( 'Grid Ad Slot 1 (after pin 5)',          'ipin' ),
		'ipin_ad_grid_2'     => __( 'Grid Ad Slot 2 (after pin 10)',         'ipin' ),
		'ipin_ad_grid_3'     => __( 'Grid Ad Slot 3 (after pin 15)',         'ipin' ),
		'ipin_ad_grid_4'     => __( 'Grid Ad Slot 4 (after pin 20)',         'ipin' ),
		'ipin_ad_grid_5'     => __( 'Grid Ad Slot 5 (after pin 25)',         'ipin' ),
		'ipin_ad_above_photo'=> __( 'Single Post — Above Featured Photo',    'ipin' ),
		'ipin_ad_below_photo'=> __( 'Single Post — Below Featured Photo',    'ipin' ),
	];
}


/* -------------------------------------------------------
   RENDER HELPER
   Returns the ad HTML or empty string. Wraps in semantic div.
   ------------------------------------------------------- */
function ipin_render_ad( string $slot ): string {
	$code = get_option( $slot, '' );
	if ( ! trim( $code ) ) return '';

	return '<div class="ipin-ad ipin-ad--' . esc_attr( str_replace( 'ipin_ad_', '', $slot ) ) . '" aria-label="' . esc_attr__( 'Advertisement', 'ipin' ) . '">'
		. $code   // raw HTML — admin-controlled, no escaping
		. '</div>';
}

/**
 * Echo the ad for a given slot (convenience wrapper).
 */
function ipin_ad( string $slot ): void {
	echo ipin_render_ad( $slot ); // phpcs:ignore WordPress.Security.EscapeOutput
}


/* -------------------------------------------------------
   GRID AD INSERTION
   Filters the posts array to inject ad "posts" at intervals.
   Call ipin_inject_grid_ads( $posts ) before the loop.
   ------------------------------------------------------- */
function ipin_get_grid_ads(): array {
	$ads = [];
	for ( $i = 1; $i <= 5; $i++ ) {
		$slot = "ipin_ad_grid_{$i}";
		$code = get_option( $slot, '' );
		if ( trim( $code ) ) {
			$ads[ $i * 5 ] = $code; // position: after pin 5, 10, 15, 20, 25
		}
	}
	return $ads;
}

/**
 * Given a 1-based card index, return ad HTML to inject after it (or '').
 */
function ipin_grid_ad_at( int $position ): string {
	static $grid_ads = null;
	if ( $grid_ads === null ) {
		$grid_ads = ipin_get_grid_ads();
	}
	if ( ! isset( $grid_ads[ $position ] ) ) return '';
	return '<div class="ipin-ad ipin-ad--grid thumb" aria-label="' . esc_attr__( 'Advertisement', 'ipin' ) . '">'
		. $grid_ads[ $position ]
		. '</div>';
}


/* -------------------------------------------------------
   ADMIN: Register settings for all ad slots
   (called from admin-options.php — no add_settings_section here)
   ------------------------------------------------------- */
function ipin_register_ad_settings(): void {
	foreach ( array_keys( ipin_ad_slots() ) as $slot ) {
		register_setting( 'ipin_ads_group', $slot, [
			'sanitize_callback' => 'wp_kses_post',
			'default'           => '',
		] );
	}
}
add_action( 'admin_init', 'ipin_register_ad_settings' );
