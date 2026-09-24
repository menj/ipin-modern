<?php
/**
 * Sort bar above the grid: Latest, then the most-commented pins of the
 * last 7 days, the last 30 days and all time (?popular=, handled in
 * inc/popular-posts.php). index.php loads it on the blog home.
 */

$current_sort = sanitize_key( $_GET['popular'] ?? '' );
?>
<!-- ── Popular posts sort bar ────────────────────── -->
<nav class="sort-bar" aria-label="<?php esc_attr_e( 'Sort posts', 'ipin-modern' ); ?>">
	<a class="sort-bar__btn<?php echo ! $current_sort ? ' active' : ''; ?>"
	   href="<?php echo esc_url( ipin_popular_sort_url() ); ?>"
	   aria-current="<?php echo ! $current_sort ? 'page' : 'false'; ?>">
		<?php esc_html_e( 'Latest', 'ipin-modern' ); ?>
	</a>
	<a class="sort-bar__btn<?php echo $current_sort === '7days' ? ' active' : ''; ?>"
	   href="<?php echo esc_url( ipin_popular_sort_url( '7days' ) ); ?>"
	   aria-current="<?php echo $current_sort === '7days' ? 'page' : 'false'; ?>">
		<?php echo ipin_icon( 'fire' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php esc_html_e( 'Last 7 days', 'ipin-modern' ); ?>
	</a>
	<a class="sort-bar__btn<?php echo $current_sort === '30days' ? ' active' : ''; ?>"
	   href="<?php echo esc_url( ipin_popular_sort_url( '30days' ) ); ?>"
	   aria-current="<?php echo $current_sort === '30days' ? 'page' : 'false'; ?>">
		<?php echo ipin_icon( 'chart-line' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php esc_html_e( 'This month', 'ipin-modern' ); ?>
	</a>
	<a class="sort-bar__btn<?php echo $current_sort === 'all' ? ' active' : ''; ?>"
	   href="<?php echo esc_url( ipin_popular_sort_url( 'all' ) ); ?>"
	   aria-current="<?php echo $current_sort === 'all' ? 'page' : 'false'; ?>">
		<?php echo ipin_icon( 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php esc_html_e( 'All time', 'ipin-modern' ); ?>
	</a>
</nav>
