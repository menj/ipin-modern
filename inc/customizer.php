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

	if ( ! class_exists( 'Ipin_Customize_Notice_Control' ) ) {
		/** Prints a static notice; stores nothing. */
		class Ipin_Customize_Notice_Control extends \WP_Customize_Control {
			public $type = 'ipin-notice';

			public function render_content(): void {
				printf(
					'<p>%s</p><p><a class="button button-primary" href="%s">%s</a></p>',
					esc_html__( 'Colour scheme, dark mode, the homepage hero, social profiles and layout are managed on the iPin Settings page.', 'ipin' ),
					esc_url( admin_url( 'themes.php?page=ipin-settings' ) ),
					esc_html__( 'Open iPin Settings', 'ipin' )
				);
			}
		}
	}

	// ── Site Identity (handled natively by WP) ──────────
	// custom-logo, site title, tagline — already registered
	// by add_theme_support( 'custom-logo' ) in functions.php.

	// ── Custom Background (supplemental) ────────────────
	// add_theme_support( 'custom-background' ) in functions.php
	// registers this section automatically. Nothing more needed.

	// ── Colours: the scheme picker replaces core's Colors section ──
	// custom-background puts its colour control in that section, so move
	// it next to the background image before removing the section.
	$bg_color = $wp_customize->get_control( 'background_color' );
	if ( $bg_color ) {
		$bg_color->section = 'background_image';
	}
	$wp_customize->remove_section( 'colors' );

	// ── Notice pointing admins to the settings page ──────
	// A section with no controls is never displayed, so the notice is a
	// setting-less control that only prints its content.
	$wp_customize->add_section( 'ipin_customizer_notice', [
		'title'    => __( 'iPin Theme Settings', 'ipin' ),
		'priority' => 1,
	] );
	$wp_customize->add_control( new Ipin_Customize_Notice_Control( $wp_customize, 'ipin_customizer_notice', [
		'section'  => 'ipin_customizer_notice',
		'settings' => [],
	] ) );
}
add_action( 'customize_register', 'ipin_customizer_register' );
