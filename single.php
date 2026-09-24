<?php get_header(); ?>

<main id="main-content" tabindex="-1">
<div class="content-area">

	<?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-wrapper' ); ?>>

		<header class="h1-wrapper">
			<h1><?php the_title(); ?></h1>
		</header>

		<div class="post-meta-top">
			<span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php echo esc_html( ipin_human_time_diff( (int) get_post_time( 'U', true ) ) ); ?>
				</time>
				&mdash;
				<span><?php the_author(); ?></span>
			</span>
			<span class="meta-right">
				<a href="#comments">
					<?php comments_number(
						esc_html__( '0 Comments', 'ipin' ),
						esc_html__( '1 Comment', 'ipin' ),
						/* translators: %s = comment count */
						esc_html__( '% Comments', 'ipin' )
					); ?>
				</a>
				<?php edit_post_link( esc_html__( 'Edit', 'ipin' ), ' &mdash; ', '' ); ?>
			</span>
		</div>

		<div class="post-content">
			<?php
			$video = ipin_post_video( get_the_ID() );
			if ( $video ) {
				// Video pin: the player replaces the featured image, which
				// becomes the poster for direct files.
				echo ipin_video_player( get_the_ID(), $video ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
			} elseif ( has_post_thumbnail() ) {
				the_post_thumbnail( 'large', [
					'class' => 'aligncenter',
					'alt'   => get_the_title(),
					// Pairs with the same name on this post's grid card, so
					// supporting browsers morph the image across navigation.
					'style' => 'view-transition-name: ipin-media-' . get_the_ID(),
				] );
			}
			the_content();
			wp_link_pages( [
				'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Post pages', 'ipin' ) . '"><strong>' . esc_html__( 'Pages:', 'ipin' ) . '</strong>',
				'after'  => '</nav>',
			] );
			?>
			<div class="clearfix"></div>
		</div>

		<footer class="post-meta-category-tag">
			<strong><?php esc_html_e( 'Categories:', 'ipin' ); ?></strong>
			<?php the_category( ', ' ); ?>
			<?php the_tags( ' &mdash; <strong>' . esc_html__( 'Tags:', 'ipin' ) . '</strong> ', ', ' ); ?>
		</footer>

		<!-- ── Share buttons ───────────────────────── -->
		<?php
		$share_url   = rawurlencode( get_permalink() );
		$share_title = rawurlencode( get_the_title() );
		$share_img   = '';
		if ( has_post_thumbnail() ) {
			$src       = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
			$share_img = $src ? rawurlencode( $src[0] ) : '';
		}
		?>
		<div class="post-share" aria-label="<?php esc_attr_e( 'Share this post', 'ipin' ); ?>">
			<span class="post-share__label"><?php esc_html_e( 'Share:', 'ipin' ); ?></span>

			<a class="btn-share btn-share--pinterest"
			   href="https://pinterest.com/pin/create/button/?url=<?php echo $share_url; ?>&media=<?php echo $share_img; ?>&description=<?php echo $share_title; ?>"
			   target="_blank" rel="noopener noreferrer"
			   aria-label="<?php esc_attr_e( 'Save to Pinterest (opens in new tab)', 'ipin' ); ?>">
				<?php echo ipin_social_icon( 'pinterest' ); ?>
				<span class="btn-share__label">Pinterest</span>
			</a>

			<a class="btn-share btn-share--twitter"
			   href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
			   target="_blank" rel="noopener noreferrer"
			   aria-label="<?php esc_attr_e( 'Share on X / Twitter (opens in new tab)', 'ipin' ); ?>">
				<?php echo ipin_social_icon( 'x' ); ?>
				<span class="btn-share__label">X</span>
			</a>

			<a class="btn-share btn-share--facebook"
			   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
			   target="_blank" rel="noopener noreferrer"
			   aria-label="<?php esc_attr_e( 'Share on Facebook (opens in new tab)', 'ipin' ); ?>">
				<?php echo ipin_social_icon( 'facebook' ); ?>
				<span class="btn-share__label">Facebook</span>
			</a>

			<button class="btn-share btn-share--copy"
			        data-copy-url="<?php echo esc_attr( get_permalink() ); ?>"
			        aria-label="<?php esc_attr_e( 'Copy link to clipboard', 'ipin' ); ?>">
				<i class="fa fa-link" aria-hidden="true"></i>
				<span class="btn-share__label"><?php esc_html_e( 'Copy link', 'ipin' ); ?></span>
			</button>
		</div><!-- /.post-share -->

		<nav id="navigation" class="post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'ipin' ); ?>">
			<ul class="pager" role="list">
				<li class="previous"><?php previous_post_link( '%link', '<span aria-hidden="true">&laquo;</span> %title' ); ?></li>
				<li class="next"><?php next_post_link( '%link', '%title <span aria-hidden="true">&raquo;</span>' ); ?></li>
			</ul>
		</nav>

		<div class="post-comments">
			<?php comments_template(); ?>
		</div>

	</article>

	<?php endwhile; ?>

</div>
</main>

<?php get_footer(); ?>
