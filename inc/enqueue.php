<?php
/**
 * iPin Modern — Asset Enqueue
 *
 * All wp_enqueue_style() and wp_enqueue_script() calls.
 * CSS lives in /assets/css/, JS in /assets/js/.
 *
 * Load order:
 *   CSS:  tokens → base → nav → masonry (archive only) → single
 *   JS:   masonry libs (archive, head) → ipin.custom (footer)
 *   Admin: admin.css + ipin.admin.js (admin page only)
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   FRONT-END ASSETS
   ------------------------------------------------------- */
function ipin_enqueue_assets(): void {
	$uri = get_template_directory_uri();
	$v   = wp_get_theme()->get( 'Version' );

	// ── External: Google Fonts ──────────────────────────
	wp_enqueue_style(
		'ipin-google-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Syne:wght@600;700;800&display=swap',
		[],
		null   // external — no version hash
	);

	// ── External: Font Awesome 6 ────────────────────────
	wp_enqueue_style(
		'ipin-font-awesome',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
		[],
		'6.5.0'
	);

	// ── CSS modules — /assets/css/ ──────────────────────
	wp_enqueue_style( 'ipin-tokens',  "$uri/assets/css/tokens.css",  [ 'ipin-google-fonts' ], $v );
	wp_enqueue_style( 'ipin-base',    "$uri/assets/css/base.css",    [ 'ipin-tokens' ],       $v );
	wp_enqueue_style( 'ipin-nav',     "$uri/assets/css/nav.css",     [ 'ipin-base' ],         $v );
	wp_enqueue_style( 'ipin-single',  "$uri/assets/css/single.css",  [ 'ipin-base' ],         $v );
	wp_enqueue_style( 'ipin-lightbox',"$uri/assets/css/lightbox.css",[ 'ipin-base' ],         $v );

	// Masonry grid CSS — archive / search / front-page only
	if ( ! is_singular() ) {
		wp_enqueue_style( 'ipin-masonry-css', "$uri/assets/css/masonry.css", [ 'ipin-base' ], $v );
	}

	// Required WP theme stylesheet (header comment only — no actual rules)
	wp_enqueue_style( 'ipin-style', get_stylesheet_uri(), [ 'ipin-tokens' ], $v );

	// ── Comment reply ───────────────────────────────────
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// ── JS: masonry libraries — /assets/js/ ─────────────
	// Loaded in <head> (in_footer = false) so masonry can
	// initialise as soon as jQuery and images are ready.
	if ( ! is_singular() ) {
		wp_enqueue_script(
			'ipin-masonry',
			"$uri/assets/js/jquery.masonry.min.js",
			[ 'jquery' ], '3.3.2', false
		);
		wp_enqueue_script(
			'ipin-imagesloaded',
			"$uri/assets/js/jquery.imagesloaded.min.js",
			[ 'jquery' ], '4.1.4', false
		);
		wp_enqueue_script(
			'ipin-infinitescroll',
			"$uri/assets/js/jquery.infinitescroll.min.js",
			[ 'jquery' ], '2.1.0', false
		);
	}

	// ── JS: custom theme script — footer ────────────────
	wp_enqueue_script(
		'ipin-custom',
		"$uri/assets/js/ipin.custom.js",
		[ 'jquery' ], $v,
		true  // in footer
	);

	// ── JS: lightbox — archive pages only ───────────────
	if ( ! is_singular() ) {
		wp_enqueue_script(
			'ipin-lightbox',
			"$uri/assets/js/lightbox.js",
			[ 'jquery', 'ipin-custom' ], $v,
			true
		);
	}

	// PHP → JS data bridge
	wp_localize_script( 'ipin-custom', 'ipinData', [
		'allLoaded'       => __( 'All items loaded', 'ipin' ),
		'themeUrl'        => $uri,
		'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
		'darkModeDefault' => (int) get_option( 'ipin_dark_mode_default', 0 ),
		'colourScheme'    => sanitize_key( get_option( 'ipin_colour_scheme', 'vivid' ) ),
		'cardWidth'       => (int) get_option( 'ipin_card_width', 220 ),
		'roundedCards'    => (int) get_option( 'ipin_rounded_cards', 1 ),
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

	wp_enqueue_style(
		'ipin-admin-css',
		"$uri/assets/css/admin.css",
		[],
		$v
	);

	wp_enqueue_script(
		'ipin-admin-js',
		"$uri/assets/js/ipin.admin.js",
		[],
		$v,
		true
	);

	wp_localize_script( 'ipin-admin-js', 'ipinAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ipin_save_options' ),
		'saving'  => __( 'Saving…', 'ipin' ),
	] );
}
add_action( 'admin_enqueue_scripts', 'ipin_enqueue_admin_assets' );


/* -------------------------------------------------------
   DYNAMIC CSS + FLASH-FREE SCHEME INIT
   Injects a tiny <style> and a synchronous <script>
   into <head> (priority 1 = before wp_head assets) so
   the correct colour scheme and dark/light state are
   applied before the first paint — no FOUC.
   ------------------------------------------------------- */
function ipin_dynamic_css_and_scheme(): void {
	$scheme     = sanitize_key( get_option( 'ipin_colour_scheme', 'vivid' ) );
	$card_width = max( 140, min( 400, (int) get_option( 'ipin_card_width', 220 ) ) );
	$rounded    = (int) get_option( 'ipin_rounded_cards', 1 );
	$dark_def   = (int) get_option( 'ipin_dark_mode_default', 0 );
	$radius_lg  = $rounded ? '20px' : '6px';

	// Tiny CSS override for admin-configurable token values
	echo '<style id="ipin-dynamic-css">'
		. ":root{--card-width:{$card_width}px;--radius-lg:{$radius_lg};}"
		. "</style>\n";

	// Synchronous scheme + dark-mode initialisation
	// Must run before any CSS is painted to prevent flash.
	$scheme_js   = esc_js( $scheme );
	$dark_def_js = $dark_def ? 'true' : 'false';

	echo <<<JS
<script id="ipin-scheme-init">
(function(){
  var h=document.documentElement;
  h.setAttribute('data-scheme','{$scheme_js}');
  var s=null;
  try{s=localStorage.getItem('ipin-dark-mode');}catch(e){}
  if(s==='dark'){
    h.setAttribute('data-theme','dark');
  }else if(s==='light'){
    h.removeAttribute('data-theme');
  }else if({$dark_def_js}||window.matchMedia('(prefers-color-scheme:dark)').matches){
    h.setAttribute('data-theme','dark');
  }
})();
</script>
JS;
}
add_action( 'wp_head', 'ipin_dynamic_css_and_scheme', 1 );
