<?php get_header(); ?>

<main id="main-content" tabindex="-1">
<div class="content-area<?php
	$pos = ipin_option( 'ipin_sidebar_position', 'right' );
	if ( $pos === 'none' ) echo ' full-width';
	if ( $pos === 'left' ) echo ' left-sidebar';
?>">

	<?php if ( $pos === 'left' ) : ?>
	<aside class="sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'ipin' ); ?>">
		<?php get_sidebar( 'left' ); ?>
	</aside>
	<?php endif; ?>

	<?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-wrapper' ); ?>>

		<header class="h1-wrapper">
			<h1><?php the_title(); ?></h1>
		</header>

		<div class="post-content">
			<?php
			the_content();
			wp_link_pages( [
				'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Page sections', 'ipin' ) . '"><strong>' . esc_html__( 'Pages:', 'ipin' ) . '</strong>',
				'after'  => '</nav>',
			] );
			?>
			<div class="clearfix"></div>
		</div>

		<?php if ( comments_open() || get_comments_number() ) : ?>
		<div id="comments" class="post-comments">
			<?php comments_template(); ?>
		</div>
		<?php endif; ?>

		<?php edit_post_link( esc_html__( 'Edit this page', 'ipin' ), '<p class="post-meta-top">', '</p>' ); ?>

	</article>

	<?php endwhile; ?>

	<?php if ( $pos === 'right' || $pos === '' ) : ?>
	<aside class="sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'ipin' ); ?>">
		<?php get_sidebar( 'right' ); ?>
	</aside>
	<?php endif; ?>

</div>
</main>

<?php get_footer(); ?>
