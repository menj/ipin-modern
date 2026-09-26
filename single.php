<?php get_header(); ?>

<main id="main-content" tabindex="-1">
<div class="content-area">

	<?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-wrapper' ); ?>>

		<header class="h1-wrapper">
			<?php if ( is_singular( [ 'post', 'ipin_article' ] ) ) get_template_part( 'template-parts/breadcrumbs' ); ?>
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
						esc_html__( '0 Comments', 'ipin-modern' ),
						esc_html__( '1 Comment', 'ipin-modern' ),
						/* translators: %s = comment count */
						esc_html__( '% Comments', 'ipin-modern' )
					); ?>
				</a>
				<?php edit_post_link( esc_html__( 'Edit', 'ipin-modern' ), ' &mdash; ', '' ); ?>
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
				'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Post pages', 'ipin-modern' ) . '"><strong>' . esc_html__( 'Pages:', 'ipin-modern' ) . '</strong>',
				'after'  => '</nav>',
			] );
			?>
			<div class="clearfix"></div>
		</div>

		<?php
		// Sideblog articles carry no categories or tags; skip the footer then.
		$cat_list = get_the_category_list( ', ' );
		$tag_list = get_the_tag_list( '', ', ' );
		if ( $cat_list || $tag_list ) : ?>
		<footer class="post-meta-category-tag">
			<?php if ( $cat_list ) : ?>
			<strong><?php esc_html_e( 'Categories:', 'ipin-modern' ); ?></strong>
			<?php echo $cat_list; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built links ?>
			<?php endif; ?>
			<?php if ( $tag_list && ! is_wp_error( $tag_list ) ) : ?>
			<?php echo $cat_list ? ' &mdash; ' : ''; ?><strong><?php esc_html_e( 'Tags:', 'ipin-modern' ); ?></strong>
			<?php echo $tag_list; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built links ?>
			<?php endif; ?>
		</footer>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/share-bar' ); ?>

		<?php // Settings → Layout; posts with a hidden tag are skipped (inc/hidden-tags.php).
		if ( (int) ipin_option( 'ipin_show_post_nav', 1 ) ) : ?>
		<nav id="navigation" class="post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'ipin-modern' ); ?>">
			<ul class="pager" role="list">
				<li class="previous"><?php previous_post_link( '%link', '<span aria-hidden="true">&laquo;</span> %title' ); ?></li>
				<li class="next"><?php next_post_link( '%link', '%title <span aria-hidden="true">&raquo;</span>' ); ?></li>
			</ul>
		</nav>
		<?php endif; ?>

		<div class="post-comments">
			<?php comments_template(); ?>
		</div>

	</article>

	<?php endwhile; ?>

</div>
</main>

<?php get_footer(); ?>
