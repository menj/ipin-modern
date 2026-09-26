<?php
/**
 * iPin Modern — Pinterest
 *
 *   1. Pinterest Tag — the conversion-tracking script, printed in <head>
 *      only when Settings → Layout → Pinterest Tag ID is set. It is the one
 *      request this theme makes to another server, and only by choice.
 *
 *   2. Rich Pin meta — <meta name="pinterest-rich-pin" content="true"> on
 *      singular views opts pages into Article Rich Pins, which read the
 *      og:* tags from inc/opengraph.php. No validation step is needed.
 *
 *   3. pinterest:media — points Pinterest's crawler at the large featured
 *      image, so it never pins the logo or an avatar by mistake.
 *
 * The lightbox's Save button is governed by ipin_pinterest_save_enabled()
 * and passed to assets/js/lightbox.js as ipinData.pinterestSave.
 *
 * Restored in 5.1 from 4.5. The stray og:image:type tag 4.5 printed here
 * (always "image/jpeg") is gone; inc/opengraph.php prints the real type.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/** The Pinterest Tag ID (digits only), or '' when not configured. */
function ipin_pinterest_tag_id(): string {
	return ipin_sanitize_pinterest_tag( get_option( 'ipin_pinterest_tag_id', '' ) );
}

/** Whether the lightbox shows the Pinterest Save button. */
function ipin_pinterest_save_enabled(): bool {
	return (bool) (int) get_option( 'ipin_pinterest_hover_save', 1 );
}


/* -------------------------------------------------------
   1. PINTEREST TAG
   ------------------------------------------------------- */
function ipin_output_pinterest_tag(): void {
	$tag_id = ipin_pinterest_tag_id();
	if ( '' === $tag_id ) {
		return;
	}

	$js = '!function(e){if(!window.pintrk){window.pintrk=function(){'
		. 'window.pintrk.queue.push(Array.prototype.slice.call(arguments))};'
		. 'var n=window.pintrk;n.queue=[],n.version="3.0";'
		. 'var t=document.createElement("script");t.async=!0,t.src=e;'
		. 'var r=document.getElementsByTagName("script")[0];'
		. 'r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");'
		. 'pintrk("load",' . wp_json_encode( $tag_id ) . ',{em:""});pintrk("page");';

	// Printed through core so CSP nonce/attribute filters apply.
	wp_print_inline_script_tag( $js, [ 'id' => 'ipin-pinterest-tag' ] );
	printf(
		'<noscript><img height="1" width="1" style="display:none" alt="" src="%s"></noscript>' . "\n",
		esc_url( 'https://ct.pinterest.com/v3/?tid=' . $tag_id . '&noscript=1' )
	);
}
add_action( 'wp_head', 'ipin_output_pinterest_tag', 5 );


/* -------------------------------------------------------
   2. RICH PIN META  +  3. pinterest:media
   ------------------------------------------------------- */
function ipin_output_pinterest_meta(): void {
	if ( ! is_singular() ) {
		return;
	}

	echo '<meta name="pinterest-rich-pin" content="true">' . "\n";

	if ( has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( (int) get_post_thumbnail_id(), 'large' );
		if ( ! empty( $src[0] ) ) {
			echo '<meta name="pinterest:media" content="' . esc_url( $src[0] ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'ipin_output_pinterest_meta', 2 );
