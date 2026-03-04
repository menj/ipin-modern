<?php
/**
 * iPin Modern — Customizer Integration
 *
 * Registers Customizer sections for settings that benefit
 * from live preview (logo, custom background).
 * All other settings live in the tabbed admin page
 * (inc/admin-options.php) via wp_options.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

function ipin_customizer_register( \WP_Customize_Manager $wp_customize ): void {

	// ── Site Identity (handled natively by WP) ──────────
	// custom-logo, site title, tagline — already registered
	// by add_theme_support( 'custom-logo' ) in functions.php.

	// ── Custom Background (supplemental) ────────────────
	// add_theme_support( 'custom-background' ) in functions.php
	// registers this section automatically. Nothing more needed.

	// ── Remove default sections we don't use ────────────
	$wp_customize->remove_section( 'colors' ); // replaced by our admin colour scheme picker

	// ── Redirect notice — point admins to our settings page ──
	$wp_customize->add_section( 'ipin_customizer_notice', [
		'title'       => __( '📌 iPin Theme Settings', 'ipin' ),
		'priority'    => 1,
		'description' => sprintf(
			/* translators: %s = URL to iPin settings page */
			__( 'Most iPin options (colour scheme, social links, layout) are managed in the <a href="%s" target="_blank">iPin Settings page</a>.', 'ipin' ),
			esc_url( admin_url( 'themes.php?page=ipin-settings' ) )
		),
	] );
}
add_action( 'customize_register', 'ipin_customizer_register' );
