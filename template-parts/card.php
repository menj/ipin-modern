<?php
/**
 * One pin in the grid. index.php calls it inside the loop, passing:
 *
 *   comments_per_card  How many comment previews to show (Settings → General).
 *   previews           Comment previews for the whole page, keyed by post ID,
 *                      from ipin_comment_previews(): one query per page.
 *
 * grid.js appends further pages by fetching them and adopting these
 * .thumb elements, so the markup here is also what infinite scroll loads.
 */

$fp_comments_num = (int) ( $args['comments_per_card'] ?? 0 );
$ipin_previews   = (array) ( $args['previews'] ?? [] );
$is_video_pin    = (bool) ipin_post_video( get_the_ID() );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $is_video_pin ? 'thumb thumb--video' : 'thumb' ); ?> data-post-id="<?php the_ID(); ?>">

	<!-- Thumbnail image — decorative duplicate of the title link;
	     hidden from AT to avoid announcing the same destination twice -->
	<a href="<?php the_permalink(); ?>"
	   class="thumb-img-wrap"
	   tabindex="-1"
	   aria-hidden="true"
	   focusable="false">
		<?php
		$w        = ipin_sanitize_card_width( get_option( 'ipin_card_width', 220 ) );
		$img_id   = ipin_card_image_id( get_post() );
		$img_html = '';
		if ( $img_id ) {
			// ipin-card + srcset: the browser picks the file that fits the
			// card at the screen's density instead of one fixed 'medium'.
			$img_html = wp_get_attachment_image( $img_id, 'ipin-card', false, [
				'alt'      => the_title_attribute( [ 'echo' => false ] ),
				'sizes'    => "(max-width: 420px) calc(100vw - 48px), {$w}px",
				'loading'  => 'lazy',
				'decoding' => 'async',
				'style'    => 'view-transition-name: ipin-media-' . get_the_ID(),
			] );
		} elseif ( preg_match( '/<img[^>]+src=["\']([^"\']+)/i', get_the_content(), $match ) ) {
			// Last resort: an image hotlinked in the content (size unknown, so square).
			$img_html = sprintf(
				'<img src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" style="view-transition-name: ipin-media-%4$d">',
				esc_url( $match[1] ),
				the_title_attribute( [ 'echo' => false ] ),
				$w,
				get_the_ID()
			);
		}

		// No-image placeholder: first letter of title for the monogram
		$title_initial = mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) );
		?>

		<?php if ( $img_html ) : ?>
		<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built or escaped above ?>
		<?php else : ?>
		<!-- No-image placeholder: gradient panel with post title monogram -->
		<div class="thumb-no-image" aria-hidden="true">
			<span class="thumb-no-image__initial"><?php echo esc_html( $title_initial ); ?></span>
			<span class="thumb-no-image__icon">&#x1f4cc;</span>
		</div>
		<?php endif; ?>

		<?php if ( $is_video_pin ) : ?>
		<span class="thumb-play" aria-hidden="true">
			<svg viewBox="0 0 24 24" focusable="false"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.86l11-6.86a1 1 0 0 0 0-1.72l-11-6.86A1 1 0 0 0 8 5.14Z"/></svg>
		</span>
		<?php endif; ?>

		<!-- Hover/focus hint — decorative (aria-hidden, no pointer events).
		     One "View" chip, because a click anywhere on the image opens
		     the lightbox; a span, since an <a> inside the wrapping <a> is
		     invalid HTML. -->
		<div class="masonry-actionbar" aria-hidden="true">
			<span class="btn btn-view">
				<?php esc_html_e( 'View', 'ipin-modern' ); ?>
				<?php echo ipin_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</span>
		</div>
	</a><!-- /.thumb-img-wrap -->

	<!-- Card body — the accessible entry point for keyboard/AT users -->
	<div class="thumb-body">

		<h2 class="thumbtitle">
			<a href="<?php the_permalink(); ?>" aria-keyshortcuts="Shift+Enter"><?php the_title(); ?><?php
				if ( $is_video_pin ) {
					echo ' <span class="screen-reader-text">' . esc_html__( '(video)', 'ipin-modern' ) . '</span>';
				}
			?></a>
		</h2>

		<?php
		// Needs both core's Settings > Discussion switch and the theme's own.
		$show_avatars    = get_option( 'show_avatars' ) && (int) ipin_option( 'ipin_show_avatars_grid', 1 );
		$comments_number = get_comments_number();
		?>
		<div class="masonry-meta<?php echo ( ! $comments_number && ! $show_avatars ) ? ' text-center' : ''; ?>">
			<?php if ( $show_avatars ) : ?>
				<div class="masonry-meta-avatar" aria-hidden="true">
					<?php echo get_avatar( get_the_author_meta( 'user_email' ), 24, '', get_the_author() ); ?>
				</div>
			<?php endif; ?>
			<div>
				<span class="masonry-meta-author"><?php the_author(); ?></span>
				<?php $card_cats = get_the_category_list( ', ' ); // empty for Sideblog articles
				if ( $card_cats ) : ?>
				<?php esc_html_e( 'in', 'ipin-modern' ); ?>
				<span class="masonry-meta-content"><?php echo $card_cats; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built links ?></span>
				<?php endif; ?>
			</div>
		</div>

		<!-- Frontpage comments preview -->
		<?php
		if ( $fp_comments_num > 0 ) :
			$comments = $ipin_previews[ get_the_ID() ] ?? [];
			foreach ( $comments as $comment ) :
		?>
			<div class="masonry-meta">
				<?php if ( $show_avatars ) : ?>
					<div class="masonry-meta-avatar" aria-hidden="true">
						<?php echo get_avatar( $comment->comment_author_email, 24, '', esc_attr( $comment->comment_author ) ); ?>
					</div>
				<?php endif; ?>
				<div>
					<span class="masonry-meta-author"><?php echo esc_html( $comment->comment_author ); ?></span>
					<?php echo esc_html( wp_trim_words( ipin_comment_plain_text( $comment->comment_content ), 12 ) ); ?>
				</div>
			</div>
		<?php endforeach;

		if ( $comments_number > $fp_comments_num ) : ?>
			<div class="masonry-meta text-center">
				<a href="<?php the_permalink(); ?>#comments">
					<?php printf(
						/* translators: %d = number of comments on the pin */
						esc_html( _n( 'View all %d comment', 'View all %d comments', $comments_number, 'ipin-modern' ) ),
						$comments_number
					); ?>
				</a>
			</div>
		<?php endif; endif; ?>

	</div><!-- /.thumb-body -->
</article><!-- /.thumb -->
