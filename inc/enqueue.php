<?php
/**
 * iPin Modern — Asset Enqueue
 *
 * All wp_enqueue_style() and wp_enqueue_script() calls, plus the
 * <head> output that belongs with them: the pre-paint colour-scheme
 * script, theme-color and the fallback favicon.
 * CSS lives in /assets/css/, JS in /assets/js/, images in /assets/img/.
 *
 * Load order (handle: file):
 *   CSS    ipin-fonts → ipin-tokens → ipin-base → ipin-nav, then one of
 *          ipin-single (posts, pages) | ipin-404 | ipin-grid + ipin-lightbox
 *   JS     ipin-theme (every view, footer) → ipin-grid + ipin-lightbox
 *          (grid views)
 *   Admin  ipin-tokens + ipin-admin (CSS and JS, settings page only)
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   FRONT-END ASSETS
   ------------------------------------------------------- */
function ipin_enqueue_assets(): void {
	$uri = get_template_directory_uri();
	$v   = wp_get_theme()->get( 'Version' );

	// ── Self-hosted webfonts — /assets/fonts/ ───────────
	// EB Garamond + Sabon Next LT + Special Elite, WOFF2.
	wp_enqueue_style( 'ipin-fonts', "$uri/assets/css/fonts.css", [], $v );

	// ── CSS modules — /assets/css/ ──────────────────────
	wp_enqueue_style( 'ipin-tokens',  "$uri/assets/css/tokens.css",  [ 'ipin-fonts' ], $v );
	$card_width = ipin_sanitize_card_width( get_option( 'ipin_card_width', 220 ) );
	$radius_lg  = (int) get_option( 'ipin_rounded_cards', 1 ) ? '20px' : '6px';
	wp_add_inline_style( 'ipin-tokens', ":root{--card-width:{$card_width}px;--radius-lg:{$radius_lg};}" );
	wp_enqueue_style( 'ipin-base',    "$uri/assets/css/base.css",    [ 'ipin-tokens' ],       $v );
	wp_enqueue_style( 'ipin-nav',     "$uri/assets/css/nav.css",     [ 'ipin-base' ],         $v );

	// Each view loads only the stylesheet it renders: posts/pages get
	// single.css; 404 gets 404.css; the grid (home, archives, search)
	// gets grid + lightbox. Footer, search form and scroll-to-top
	// live in base.css.
	if ( is_singular() ) {
		wp_enqueue_style( 'ipin-single', "$uri/assets/css/single.css", [ 'ipin-base' ], $v );
	} elseif ( is_404() ) {
		wp_enqueue_style( 'ipin-404', "$uri/assets/css/404.css", [ 'ipin-base' ], $v );
	} else {
		wp_enqueue_style( 'ipin-grid',     "$uri/assets/css/grid.css",     [ 'ipin-base' ], $v );
		wp_enqueue_style( 'ipin-lightbox', "$uri/assets/css/lightbox.css", [ 'ipin-base' ], $v );
	}

	// Required WP theme stylesheet (header comment only — no actual rules)
	wp_enqueue_style( 'ipin-style', get_stylesheet_uri(), [ 'ipin-tokens' ], $v );

	// ── Comment reply ───────────────────────────────────
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// ── JS: custom theme script — footer, no jQuery ─────
	wp_enqueue_script(
		'ipin-theme',
		"$uri/assets/js/theme.js",
		[], $v,
		true  // in footer
	);

	// ── JS: grid engine + lightbox — archive pages only ─
	// Vanilla masonry, infinite scroll, and lightbox; the
	// bundled jQuery plugin stack is gone.
	if ( ! is_singular() && ! is_404() ) {
		wp_enqueue_script(
			'ipin-grid',
			"$uri/assets/js/grid.js",
			[ 'ipin-theme' ], $v,   // after ipin-theme so ipinData is localized first
			true
		);
		wp_enqueue_script(
			'ipin-lightbox',
			"$uri/assets/js/lightbox.js",
			[ 'ipin-theme' ], $v,
			true
		);
	}

	// PHP → JS data bridge
	wp_localize_script( 'ipin-theme', 'ipinData', [
		'allLoaded'   => __( 'All items loaded', 'ipin-modern' ),
		'loadingText' => __( 'Loading more pins…', 'ipin-modern' ),
		'i18n'        => [
			'comments'      => __( 'Comments', 'ipin-modern' ),
			'viewAll'       => __( 'View all', 'ipin-modern' ),
			'view'          => __( 'View', 'ipin-modern' ),
			'close'         => __( 'Close lightbox', 'ipin-modern' ),
			'prev'          => __( 'Previous pin', 'ipin-modern' ),
			'next'          => __( 'Next pin', 'ipin-modern' ),
			'pinError'      => __( 'This pin could not be loaded.', 'ipin-modern' ),
			'pinErrorHint'  => __( 'Please try again, or open the post directly.', 'ipin-modern' ),
			'sharePinterest'=> __( 'Save to Pinterest (opens in new tab)', 'ipin-modern' ),
			'shareX'        => __( 'Share on X (opens in new tab)', 'ipin-modern' ),
			'shareFacebook' => __( 'Share on Facebook (opens in new tab)', 'ipin-modern' ),
			'copied'        => __( 'Copied!', 'ipin-modern' ),
			'linkCopied'    => __( 'Link copied to clipboard.', 'ipin-modern' ),
		],
		'pinUrl'      => esc_url_raw( rest_url( 'ipin/v1/pin/' ) ),
		'icons'       => [
			'pinterest' => ipin_social_icon( 'pinterest' ),
			'x'         => ipin_social_icon( 'x' ),
			'facebook'  => ipin_social_icon( 'facebook' ),
		],
	] );
}
add_action( 'wp_enqueue_scripts', 'ipin_enqueue_assets' );


/* -------------------------------------------------------
   ADMIN ASSETS  (admin page only)
   ------------------------------------------------------- */
function ipin_enqueue_admin_assets( string $hook ): void {
	if ( 'appearance_page_ipin-settings' !== $hook ) {
		return;
	}

	$uri = get_template_directory_uri();
	$v   = wp_get_theme()->get( 'Version' );

	// tokens.css holds only custom properties; the scheme swatches read
	// their gradients from it so the picker always matches the site.
	wp_enqueue_style( 'ipin-tokens', "$uri/assets/css/tokens.css", [], $v );
	wp_enqueue_style(
		'ipin-admin',
		"$uri/assets/css/admin.css",
		[ 'ipin-tokens' ],
		$v
	);

	wp_enqueue_script(
		'ipin-admin',
		"$uri/assets/js/admin.js",
		[],
		$v,
		true
	);

	wp_localize_script( 'ipin-admin', 'ipinAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ipin_save_options' ),
		'saving'  => __( 'Saving…', 'ipin-modern' ),
		'error'   => __( 'Settings could not be saved. Check your connection and try again.', 'ipin-modern' ),
	] );
}
add_action( 'admin_enqueue_scripts', 'ipin_enqueue_admin_assets' );


/* -------------------------------------------------------
   BROWSER CHROME COLOUR (theme-color)
   Per scheme: its lead vivid colour in light mode, its nav
   surface in dark mode (mirrors tokens.css). The pre-paint
   script and the dark-mode toggle swap between the two.
   ------------------------------------------------------- */
function ipin_theme_colors(): array {
	$map = [
		'vivid'  => [ '#FF3CAC', '#130E20' ],
		'ocean'  => [ '#00C9B1', '#030D1C' ],
		'ember'  => [ '#FF4D00', '#1A0900' ],
		'forest' => [ '#52B788', '#041208' ],
		'mono'   => [ '#444444', '#111111' ],
	];
	[ $light, $dark ] = $map[ ipin_sanitize_scheme( get_option( 'ipin_colour_scheme', 'vivid' ) ) ];
	return [ 'light' => $light, 'dark' => $dark ];
}


/* -------------------------------------------------------
   FLASH-FREE SCHEME INIT
   A synchronous <script> in <head> (priority 1, before
   wp_head assets) applies the colour scheme and dark/light
   state before the first paint — no FOUC. The admin's card
   width / corner settings travel as wp_add_inline_style()
   on the ipin-tokens handle (see ipin_enqueue_assets).
   ------------------------------------------------------- */
function ipin_dynamic_css_and_scheme(): void {
	$scheme      = ipin_sanitize_scheme( get_option( 'ipin_colour_scheme', 'vivid' ) );
	$dark_def_js = (int) get_option( 'ipin_dark_mode_default', 0 ) ? 'true' : 'false';

	// Synchronous scheme + dark-mode initialisation. Must run before any
	// CSS is painted to prevent a flash, so it stays inline in <head>;
	// printed through core so CSP nonce/attribute filters apply.
	$js = "(function(){var h=document.documentElement;h.setAttribute('data-scheme'," . wp_json_encode( $scheme ) . ");"
		. "var s=null;try{s=localStorage.getItem('ipin-dark-mode');}catch(e){}"
		. "if(s==='dark'){h.setAttribute('data-theme','dark');}"
		. "else if(s==='light'){h.removeAttribute('data-theme');}"
		. "else if({$dark_def_js}||window.matchMedia('(prefers-color-scheme:dark)').matches){h.setAttribute('data-theme','dark');}"
		. "var m=document.querySelector('meta[name=\"theme-color\"]');"
		. "if(m){m.content=h.getAttribute('data-theme')==='dark'?m.dataset.dark:m.dataset.light;}})();";

	wp_print_inline_script_tag( $js, [ 'id' => 'ipin-scheme-init' ] );
}
add_action( 'wp_head', 'ipin_dynamic_css_and_scheme', 1 );

/* -------------------------------------------------------
   FAVICON FALLBACK
   Prints the bundled brand favicon only when no Site Icon
   is set (Customizer → Site Identity → Site Icon). When a
   Site Icon exists, core outputs its own <link> tags and
   this fallback stays silent so the owner's icon wins.
   ------------------------------------------------------- */
function ipin_favicon_fallback(): void {
	if ( has_site_icon() ) {
		return;
	}
	$base = get_template_directory_uri() . '/assets/img';
	echo '<link rel="icon" href="' . esc_url( $base . '/favicon.ico' ) . '" sizes="48x48">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $base . '/favicon.svg' ) . '" type="image/svg+xml" sizes="any">' . "\n";
}
add_action( 'wp_head', 'ipin_favicon_fallback', 2 );
