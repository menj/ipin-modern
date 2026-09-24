<?php get_header(); ?>

<main id="main-content" tabindex="-1">
	<section class="empty-state" aria-labelledby="error-404-heading">
		<span class="empty-icon" aria-hidden="true">🔍</span>
		<h1 id="error-404-heading"><?php esc_html_e( 'Page not found', 'ipin-modern' ); ?></h1>
		<p><?php esc_html_e( "Sorry, we couldn't find what you were looking for. Try searching instead.", 'ipin-modern' ); ?></p>
		<?php get_search_form(); ?>
	</section>
</main>

<?php get_footer(); ?>
