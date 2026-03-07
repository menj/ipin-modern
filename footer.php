<footer id="footer" role="contentinfo">
	<p>
		&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<?php
		$desc = get_bloginfo( 'description' );
		if ( $desc ) {
			echo ' &mdash; ' . esc_html( $desc );
		}
		$footer_text = ipin_option( 'ipin_footer_text', '' );
		if ( $footer_text ) {
			echo ' &mdash; ' . wp_kses_post( $footer_text );
		}
		?>
	</p>
</footer>

<!-- Visually-hidden aria-live region for status announcements
     e.g. "Link copied!" from the copy-link share button (WCAG 4.1.3) -->
<div id="ipin-live-region"
     role="status"
     aria-live="polite"
     aria-atomic="true"
     class="sr-only"></div>

<!-- Scroll-to-top button
     - aria-hidden when not yet visible; JS removes aria-hidden + sets aria-label
     - Uses <button> not <a href="#"> so it's operable without a pointing device -->
<button id="scrolltotop"
        aria-label="<?php esc_attr_e( 'Scroll back to top', 'ipin' ); ?>"
        aria-hidden="true"
        hidden>
	<i class="fa fa-chevron-up" aria-hidden="true"></i>
	<span class="sr-only"><?php esc_html_e( 'Top', 'ipin' ); ?></span>
</button>

<?php wp_footer(); ?>
</body>
</html>
