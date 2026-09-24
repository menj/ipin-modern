<?php get_header(); ?>

<!-- main landmark targets the skip link (WCAG 2.4.1) -->
<main id="main-content" tabindex="-1">
<div id="masonry-wrap">

	<?php
	// Homepage hero and sort bar: blog home only; the hero on its first page.
	if ( ( is_home() || is_front_page() ) && ! is_paged() && (int) ipin_option( 'ipin_hero_enabled', 1 ) ) {
		get_template_part( 'template-parts/home-hero' );
	}
	if ( is_home() || is_front_page() ) {
		get_template_part( 'template-parts/sort-bar' );
	}
	?>

	<?php if ( have_posts() ) : ?>

		<!-- aria-busy cleared by grid.js after first layout -->
		<div id="masonry" aria-busy="true">
			<?php
			// Comment previews for the whole page come from one query.
			$per_card  = (int) ipin_option( 'ipin_frontpage_comments', 3 );
			$card_args = [
				'comments_per_card' => $per_card,
				'previews'          => ipin_comment_previews( $GLOBALS['wp_query']->posts, $per_card ),
			];
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', null, $card_args );
			endwhile;
			?>
		</div><!-- /#masonry -->

		<!-- Pagination — landmark nav with distinct label (WCAG 2.4.6) -->
		<nav id="navigation" aria-label="<?php esc_attr_e( 'Posts pagination', 'ipin-modern' ); ?>">
			<ul class="pager" role="list">
				<li id="navigation-next">
					<?php next_posts_link( '<span aria-hidden="true">&laquo;</span> ' . esc_html__( 'Older posts', 'ipin-modern' ) ); ?>
				</li>
				<li id="navigation-previous">
					<?php previous_posts_link( esc_html__( 'Newer posts', 'ipin-modern' ) . ' <span aria-hidden="true">&raquo;</span>' ); ?>
				</li>
			</ul>
		</nav>

	<?php else : ?>

		<section class="empty-state" aria-label="<?php esc_attr_e( 'No content found', 'ipin-modern' ); ?>">
			<span class="empty-icon" aria-hidden="true">📌</span>
			<h1><?php esc_html_e( 'Nothing pinned here yet', 'ipin-modern' ); ?></h1>
			<p><?php esc_html_e( 'Perhaps searching will help.', 'ipin-modern' ); ?></p>
			<?php get_search_form(); ?>
		</section>

	<?php endif; ?>
</div><!-- /#masonry-wrap -->
</main>

<?php get_footer(); ?>
