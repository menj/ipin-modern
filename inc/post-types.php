<?php
/**
 * iPin Modern — Sideblog post type (ipin_article)
 *
 * Post types are plugin territory: registered by a theme, their content
 * disappears from the admin the moment the theme is switched. The
 * registration therefore lives in the companion plugin at
 * companion/ipin-sideblog/. When that plugin is active it has already
 * defined the post type; when it isn't, the theme loads the very same
 * file so existing sites keep working unchanged.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'IPIN_SIDEBLOG_LOADED' ) ) {
	require_once get_template_directory() . '/companion/ipin-sideblog/ipin-sideblog.php';
}

add_action( 'after_switch_theme', static function (): void {
	ipin_sideblog_register();
	flush_rewrite_rules();
} );


/* -------------------------------------------------------
   RECOMMEND THE COMPANION PLUGIN
   Shown on the iPin Settings page only (never site-wide),
   and only while the theme is providing the fallback.
   ------------------------------------------------------- */
function ipin_sideblog_notice(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'appearance_page_ipin-settings' !== $screen->id || ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	if ( str_starts_with( wp_normalize_path( ( new ReflectionFunction( 'ipin_sideblog_register' ) )->getFileName() ), wp_normalize_path( WP_PLUGIN_DIR ) ) ) {
		return;   // the plugin is active and owns the post type
	}
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		wp_kses(
			sprintf(
				/* translators: %s = folder path inside the theme */
				__( '<strong>Keep your Sideblog articles safe:</strong> copy %s from the iPin Modern theme folder to <code>wp-content/plugins/</code> and activate <em>iPin Sideblog</em>. Your articles then stay available even if you switch themes.', 'ipin' ),
				'<code>companion/ipin-sideblog</code>'
			),
			[ 'strong' => [], 'code' => [], 'em' => [] ]
		)
	);
}
add_action( 'admin_notices', 'ipin_sideblog_notice' );
