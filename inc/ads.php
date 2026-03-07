<?php
/**
 * iPin Modern — Ad Slot Manager
 *
 * Slots:
 *   ipin_ad_header          — top of every page, full-width banner
 *   ipin_ad_grid_{1-5}      — injected into masonry grid (positions 5,10,15,20,25)
 *   ipin_ad_above_photo     — single post, above featured image
 *   ipin_ad_below_photo     — single post, below featured image
 *
 * Each slot stores the ad code separately from its enabled state:
 *   {slot}          — the raw HTML code (preserved across toggling)
 *   {slot}_enabled  — 1 = active, 0 = paused (code is kept, not rendered)
 *
 * A global switch ipin_manual_ads_enabled (1/0) overrides all slots at once.
 * Use this to hand off ad placement entirely to Google Site Kit Auto Ads
 * without losing any of your manually configured slot code.
 *
 * Render flow: global switch ON → slot enabled → slot has code → output HTML
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   SLOT DEFINITIONS
   ------------------------------------------------------- */
function ipin_ad_slots(): array {
	return [
		'ipin_ad_header'      => __( 'Top Header Banner (full width)',     'ipin' ),
		'ipin_ad_grid_1'      => __( 'Grid Ad Slot 1 (after pin 5)',       'ipin' ),
		'ipin_ad_grid_2'      => __( 'Grid Ad Slot 2 (after pin 10)',      'ipin' ),
		'ipin_ad_grid_3'      => __( 'Grid Ad Slot 3 (after pin 15)',      'ipin' ),
		'ipin_ad_grid_4'      => __( 'Grid Ad Slot 4 (after pin 20)',      'ipin' ),
		'ipin_ad_grid_5'      => __( 'Grid Ad Slot 5 (after pin 25)',      'ipin' ),
		'ipin_ad_above_photo' => __( 'Single Post — Above Featured Photo', 'ipin' ),
		'ipin_ad_below_photo' => __( 'Single Post — Below Featured Photo', 'ipin' ),
	];
}


/* -------------------------------------------------------
   GLOBAL SWITCH HELPER
   Returns true when manual ad slots are globally enabled.
   ------------------------------------------------------- */
function ipin_manual_ads_on(): bool {
	return (bool) get_option( 'ipin_manual_ads_enabled', 1 );
}


/* -------------------------------------------------------
   SLOT ENABLED HELPER
   Returns true when a specific slot is individually enabled.
   ------------------------------------------------------- */
function ipin_ad_slot_enabled( string $slot ): bool {
	return (bool) get_option( $slot . '_enabled', 1 );
}


/* -------------------------------------------------------
   RENDER HELPER
   Returns the ad HTML or empty string.
   Checks: global switch → slot enabled → slot has code.
   ------------------------------------------------------- */
function ipin_render_ad( string $slot ): string {
	if ( ! ipin_manual_ads_on() )      return '';
	if ( ! ipin_ad_slot_enabled( $slot ) ) return '';

	$code = get_option( $slot, '' );
	if ( ! trim( $code ) )             return '';

	$name = esc_attr( str_replace( 'ipin_ad_', '', $slot ) );
	return '<div class="ipin-ad ipin-ad--' . $name . '" aria-label="' . esc_attr__( 'Advertisement', 'ipin' ) . '">'
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
   Respects global switch + per-slot enabled state.
   ------------------------------------------------------- */
function ipin_get_grid_ads(): array {
	$ads = [];
	if ( ! ipin_manual_ads_on() ) return $ads;

	for ( $i = 1; $i <= 5; $i++ ) {
		$slot = "ipin_ad_grid_{$i}";
		if ( ! ipin_ad_slot_enabled( $slot ) ) continue;
		$code = get_option( $slot, '' );
		if ( trim( $code ) ) {
			$ads[ $i * 5 ] = $code;
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
   SANITIZATION
   Ad code contains <script> and <ins data-*> which wp_kses_post
   strips. Trust manage_options users; return '' for everyone else.
   ------------------------------------------------------- */
function ipin_sanitize_ad_code( mixed $code ): string {
	if ( ! current_user_can( 'manage_options' ) ) return '';
	return trim( wp_unslash( (string) $code ) );
}


/* -------------------------------------------------------
   ADMIN: Register settings
   Registers the global switch, all slot code options, and all
   slot enabled options.
   ------------------------------------------------------- */
function ipin_register_ad_settings(): void {
	// Global manual ads switch
	register_setting( 'ipin_ads_group', 'ipin_manual_ads_enabled', [
		'sanitize_callback' => 'absint',
		'default'           => 1,
	] );

	foreach ( array_keys( ipin_ad_slots() ) as $slot ) {
		// Ad code
		register_setting( 'ipin_ads_group', $slot, [
			'sanitize_callback' => 'ipin_sanitize_ad_code',
			'default'           => '',
		] );
		// Per-slot enabled toggle
		register_setting( 'ipin_ads_group', $slot . '_enabled', [
			'sanitize_callback' => 'absint',
			'default'           => 1,
		] );
	}
}
add_action( 'admin_init', 'ipin_register_ad_settings' );
