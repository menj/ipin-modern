<?php get_header(); ?>

<main id="main-content" tabindex="-1">
	<?php
	$quip       = ipin_404_quip();
	$random_url = ipin_random_pin_url();
	?>
	<section class="error-404" aria-labelledby="error-404-heading">

		<!-- "4 0 4" where the zero is a pin that came loose. Decorative. -->
		<div class="error-404__art" aria-hidden="true">
			<span class="error-404__digit">4</span>
			<span class="error-404__pin"></span>
			<span class="error-404__digit">4</span>
		</div>

		<p class="error-404__eyebrow"><?php esc_html_e( 'Error 404 · Page not found', 'ipin-modern' ); ?></p>
		<h1 id="error-404-heading" class="error-404__title"><?php echo esc_html( $quip['title'] ); ?></h1>
		<?php if ( '' !== $quip['text'] ) : ?>
		<p class="error-404__text"><?php echo esc_html( $quip['text'] ); ?></p>
		<?php endif; ?>

		<div class="error-404__actions">
			<a class="error-404__btn error-404__btn--primary" href="<?php echo esc_url( ipin_grid_base_url() ); ?>">
				<?php esc_html_e( 'Back to the board', 'ipin-modern' ); ?>
			</a>
			<?php if ( $random_url ) : ?>
			<a class="error-404__btn" href="<?php echo esc_url( $random_url ); ?>">
				<?php esc_html_e( 'Show me a random pin', 'ipin-modern' ); ?>
			</a>
			<?php endif; ?>
		</div>

		<div class="error-404__search">
			<p><?php esc_html_e( 'Or play detective:', 'ipin-modern' ); ?></p>
			<?php get_search_form(); ?>
		</div>

	</section>
</main>

<?php get_footer(); ?>
