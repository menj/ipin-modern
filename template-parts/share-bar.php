<?php
/**
 * Share buttons under a single post or article. Use inside the loop.
 *
 *   Share via…  the device's own share sheet; theme.js reveals it only
 *               where the Web Share API exists (mostly phones)
 *   WhatsApp, Telegram, Facebook, X, Threads, Mastodon, Pinterest
 *               (Pinterest with the featured image)
 *   Email, Copy link (copy is handled in theme.js)
 *
 * X gets the post's tags as &hashtags=; Threads and Mastodon get them
 * inline as #CamelCase (ipin_share_hashtags(); hidden tags are never used).
 * WhatsApp, Telegram, Threads, Mastodon, Email and native share were
 * restored in 5.1 from 4.5.
 */

$share_link  = (string) get_permalink();
$share_name  = wp_strip_all_tags( get_the_title() );
$share_url   = rawurlencode( $share_link );
$share_title = rawurlencode( $share_name );
$share_text  = rawurlencode( $share_name . ' ' . $share_link );
$hashtags    = ipin_share_hashtags( get_the_ID() );
$share_ht    = rawurlencode( $share_name . ' ' . $share_link . $hashtags['inline'] );
$share_img   = '';
if ( has_post_thumbnail() ) {
	$src       = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
	$share_img = $src ? rawurlencode( $src[0] ) : '';
}
?>
<!-- ── Share buttons ───────────────────────── -->
<div class="post-share" aria-label="<?php esc_attr_e( 'Share this post', 'ipin-modern' ); ?>">
	<span class="post-share__label"><?php esc_html_e( 'Share:', 'ipin-modern' ); ?></span>

	<button type="button" class="btn-share btn-share--native" hidden
	        data-share-url="<?php echo esc_attr( $share_link ); ?>"
	        data-share-title="<?php echo esc_attr( $share_name ); ?>"
	        aria-label="<?php esc_attr_e( 'Share using your device', 'ipin-modern' ); ?>">
		<?php echo ipin_icon( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="btn-share__label"><?php esc_html_e( 'Share via…', 'ipin-modern' ); ?></span>
	</button>

	<a class="btn-share btn-share--whatsapp"
	   href="https://wa.me/?text=<?php echo esc_attr( $share_text ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on WhatsApp (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'whatsapp' ); ?>
		<span class="btn-share__label">WhatsApp</span>
	</a>

	<a class="btn-share btn-share--telegram"
	   href="https://t.me/share/url?url=<?php echo esc_attr( $share_url ); ?>&amp;text=<?php echo esc_attr( $share_title ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on Telegram (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'telegram' ); ?>
		<span class="btn-share__label">Telegram</span>
	</a>

	<a class="btn-share btn-share--facebook"
	   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $share_url ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on Facebook (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'facebook' ); ?>
		<span class="btn-share__label">Facebook</span>
	</a>

	<a class="btn-share btn-share--twitter"
	   href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $share_url ); ?>&amp;text=<?php echo esc_attr( $share_title ); ?><?php echo $hashtags['twitter'] ? '&amp;hashtags=' . esc_attr( rawurlencode( $hashtags['twitter'] ) ) : ''; ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on X / Twitter (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'x' ); ?>
		<span class="btn-share__label">X</span>
	</a>

	<a class="btn-share btn-share--threads"
	   href="https://www.threads.net/intent/post?text=<?php echo esc_attr( $share_ht ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on Threads (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'threads' ); ?>
		<span class="btn-share__label">Threads</span>
	</a>

	<a class="btn-share btn-share--mastodon"
	   href="https://mastodon.social/share?text=<?php echo esc_attr( $share_ht ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Share on Mastodon (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'mastodon' ); ?>
		<span class="btn-share__label">Mastodon</span>
	</a>

	<a class="btn-share btn-share--pinterest"
	   href="https://pinterest.com/pin/create/button/?url=<?php echo esc_attr( $share_url ); ?>&amp;media=<?php echo esc_attr( $share_img ); ?>&amp;description=<?php echo esc_attr( $share_title ); ?>"
	   target="_blank" rel="noopener noreferrer"
	   aria-label="<?php esc_attr_e( 'Save to Pinterest (opens in new tab)', 'ipin-modern' ); ?>">
		<?php echo ipin_social_icon( 'pinterest' ); ?>
		<span class="btn-share__label">Pinterest</span>
	</a>

	<a class="btn-share btn-share--email"
	   href="mailto:?subject=<?php echo esc_attr( $share_title ); ?>&amp;body=<?php echo esc_attr( $share_text ); ?>"
	   aria-label="<?php esc_attr_e( 'Share by email', 'ipin-modern' ); ?>">
		<?php echo ipin_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="btn-share__label"><?php esc_html_e( 'Email', 'ipin-modern' ); ?></span>
	</a>

	<button type="button" class="btn-share btn-share--copy"
	        data-copy-url="<?php echo esc_attr( $share_link ); ?>"
	        aria-label="<?php esc_attr_e( 'Copy link to clipboard', 'ipin-modern' ); ?>">
		<?php echo ipin_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="btn-share__label"><?php esc_html_e( 'Copy link', 'ipin-modern' ); ?></span>
	</button>
</div><!-- /.post-share -->
