<?php
/**
 * Share buttons under a single post: Pinterest (with the featured image),
 * X, Facebook, and copy-link (handled in assets/js/theme.js).
 * Use inside the loop.
 */

$share_url   = rawurlencode( get_permalink() );
$share_title = rawurlencode( get_the_title() );
$share_img   = '';
if ( has_post_thumbnail() ) {
	$src       = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
	$share_img = $src ? rawurlencode( $src[0] ) : '';
}
?>
<!-- ── Share buttons ───────────────────────── -->
<div class="post-share" aria-label="<?php esc_attr_e( 'Share this post', 'ipin-modern' ); ?>">
	<span class="post-share__label"><?php esc_html_e( 'Share:', 'ipin-modern' ); ?></span>

	<a class="btn-share btn-share--pinterest"
	   href="https://pinterest.com/pin/create/button/?url=<?php echo $share_url; ?>&media=<?php echo $share_img; ?>&description=<?php echo $share_title; ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Save to Pinterest (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'pinterest' ); ?>
		<span class="btn-share__label">Pinterest</span>
	</a>

	<a class="btn-share btn-share--twitter"
	   href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on X / Twitter (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'x' ); ?>
		<span class="btn-share__label">X</span>
	</a>

	<a class="btn-share btn-share--facebook"
	   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on Facebook (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'facebook' ); ?>
		<span class="btn-share__label">Facebook</span>
	</a>

	<button class="btn-share btn-share--copy"
	        data-copy-url="<?php echo esc_attr( get_permalink() ); ?>"
	        aria-label="<?php esc_attr_e( 'Copy link to clipboard', 'ipin-modern' ); ?>">
		<?php echo ipin_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="btn-share__label"><?php esc_html_e( 'Copy link', 'ipin-modern' ); ?></span>
	</button>
</div><!-- /.post-share -->
