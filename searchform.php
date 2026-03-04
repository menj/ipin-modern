<form class="search-form-wrap" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
	<label for="search-form-input" class="sr-only"><?php esc_html_e( 'Search', 'ipin' ); ?></label>
	<input
		type="search"
		id="search-form-input"
		name="s"
		placeholder="<?php esc_attr_e( 'Search…', 'ipin' ); ?>"
		value="<?php echo esc_attr( get_search_query() ); ?>"
	>
	<button type="submit"><?php esc_html_e( 'Search', 'ipin' ); ?></button>
</form>
