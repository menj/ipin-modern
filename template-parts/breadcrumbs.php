<?php
/**
 * Breadcrumb trail above a post or article title: Home › Category › Title
 * (articles: Home › Articles › Title). Google's starter guide recommends
 * breadcrumbs for readers, and the BreadcrumbList structured data in
 * inc/seo.php is built from the same ipin_breadcrumb_items() list, so the
 * markup always matches what is on screen. Use inside the loop.
 */

$ipin_crumbs = ipin_breadcrumb_items( get_the_ID() );
if ( count( $ipin_crumbs ) < 2 ) {
	return;
}
?>
<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ipin-modern' ); ?>">
	<ol role="list">
		<?php foreach ( $ipin_crumbs as [ $ipin_name, $ipin_url ] ) : ?>
		<li>
			<?php if ( '' !== $ipin_url ) : ?>
			<a href="<?php echo esc_url( $ipin_url ); ?>"><?php echo esc_html( $ipin_name ); ?></a>
			<?php else : ?>
			<span aria-current="page"><?php echo esc_html( $ipin_name ); ?></span>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ol>
</nav>
