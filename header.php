<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $ipin_tc = ipin_theme_colors(); ?>
	<meta name="theme-color" content="<?php echo esc_attr( $ipin_tc['light'] ); ?>"
	      data-light="<?php echo esc_attr( $ipin_tc['light'] ); ?>"
	      data-dark="<?php echo esc_attr( $ipin_tc['dark'] ); ?>">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Skip to main content (WCAG 2.4.1) -->
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to main content', 'ipin-modern' ); ?></a>

<!-- =======================================================
     TOP NAVIGATION  (landmark: <nav>, WCAG 1.3.6 / 4.1.2)
     ======================================================= -->
<nav id="topmenu" aria-label="<?php esc_attr_e( 'Main navigation', 'ipin-modern' ); ?>">
	<div class="nav-inner">

		<!-- Brand —
		     When a custom logo is active the_custom_logo() already outputs <a href="home">
		     so we use a <span> wrapper to prevent nested <a> elements (invalid HTML,
		     WCAG 4.1.1 Parsing). When no logo is set we render the text link ourselves. -->
		<?php if ( has_custom_logo() ) : ?>
		<span class="navbar-brand navbar-brand--logo">
			<?php the_custom_logo(); ?>
		</span>
		<?php else : ?>
		<a class="navbar-brand"
		   href="<?php echo esc_url( home_url( '/' ) ); ?>"
		   rel="home"
		   aria-label="<?php echo esc_attr( sprintf(
		   		/* translators: %s = site name */
		   		__( '%s — go to homepage', 'ipin-modern' ),
		   		get_bloginfo( 'name' )
		   ) ); ?>">
			<span class="navbar-brand__icon" aria-hidden="true"><?php
				// Same pin mark as the favicon — one source of truth for the brand glyph.
				echo file_get_contents( get_template_directory() . '/assets/img/favicon.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			?></span>
			<span aria-hidden="true"><?php bloginfo( 'name' ); ?></span>
		</a>
		<?php endif; ?>

		<!-- Hamburger (WCAG 4.1.2: name, role, value — aria-expanded updated by JS) -->
		<button class="navbar-toggle"
		        aria-label="<?php esc_attr_e( 'Open navigation menu', 'ipin-modern' ); ?>"
		        aria-expanded="false"
		        aria-controls="nav-main"
		        data-label-open="<?php esc_attr_e( 'Open navigation menu', 'ipin-modern' ); ?>"
		        data-label-close="<?php esc_attr_e( 'Close navigation menu', 'ipin-modern' ); ?>">
			<span class="icon-bar" aria-hidden="true"></span>
			<span class="icon-bar" aria-hidden="true"></span>
			<span class="icon-bar" aria-hidden="true"></span>
		</button>

		<div id="nav-main">

			<!-- Primary menu -->
			<?php if ( has_nav_menu( 'top_nav' ) ) : ?>
				<?php wp_nav_menu( [
					'theme_location' => 'top_nav',
					'menu_class'     => 'nav-list',
					'depth'          => 3,
					'container'      => false,
					'walker'         => new Ipin_Nav_Walker(),
					'items_wrap'     => '<ul id="%1$s" class="%2$s" role="list">%3$s</ul>',
				] ); ?>
			<?php else : ?>
				<ul class="nav-list" role="list">
					<?php wp_list_pages( [ 'title_li' => '', 'depth' => 1, 'sort_column' => 'menu_order' ] ); ?>
					<?php if ( ipin_has_articles() ) :
						$ipin_in_sideblog = is_post_type_archive( 'ipin_article' ) || is_singular( 'ipin_article' ); ?>
					<li class="page_item<?php echo $ipin_in_sideblog ? ' current_page_item' : ''; ?>">
						<a href="<?php echo esc_url( get_post_type_archive_link( 'ipin_article' ) ); ?>"<?php echo $ipin_in_sideblog ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Articles', 'ipin-modern' ); ?></a>
					</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>

			<!-- Inline search (WCAG 1.3.1: <label>, role="search") -->
			<form class="nav-search"
			      method="get"
			      action="<?php echo esc_url( home_url( '/' ) ); ?>"
			      role="search"
			      aria-label="<?php esc_attr_e( 'Site search', 'ipin-modern' ); ?>">
				<label for="nav-search-input" class="sr-only">
					<?php esc_html_e( 'Search', 'ipin-modern' ); ?>
				</label>
				<input
					type="search"
					id="nav-search-input"
					name="s"
					placeholder="<?php esc_attr_e( 'Search…', 'ipin-modern' ); ?>"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					autocomplete="off"
				>
				<button type="submit">
					<?php echo ipin_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="sr-only"><?php esc_html_e( 'Search', 'ipin-modern' ); ?></span>
				</button>
			</form>

			<div class="topmenu-social-wrap">

				<!-- Social links (WCAG 2.4.6: each has a descriptive label).
				     role="list" keeps list semantics in Safari, which drops
				     them from lists styled with list-style: none. -->
				<ul class="topmenu-social-list"
				    role="list"
				    aria-label="<?php esc_attr_e( 'Social links', 'ipin-modern' ); ?>">

				<?php if ( (int) ipin_option( 'ipin_rss_visible', 1 ) ) : ?>
				<li>
					<a href="<?php bloginfo( 'rss2_url' ); ?>"
					   class="topmenu-social"
					   aria-label="<?php esc_attr_e( 'RSS feed (opens in new tab)', 'ipin-modern' ); ?>"
					   target="_blank"
					   rel="noopener">
						<?php echo ipin_icon( 'rss' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</li>
				<?php endif; ?>

				<?php
				// Every profile set in Settings → Social, in ipin_social_profiles()
				// order. The first ipin_social_inline show in the bar; the rest
				// fold into a "More profiles" disclosure (a native <details>, so it
				// opens without JavaScript; theme.js closes it on Escape or an
				// outside click).
				$ipin_links = [];
				foreach ( ipin_social_profiles() as $ipin_key => [ $ipin_icon, $ipin_name ] ) {
					$ipin_url = (string) ipin_option( $ipin_key );
					if ( '' !== $ipin_url ) {
						$ipin_links[] = [ $ipin_url, $ipin_icon, $ipin_name ];
					}
				}
				$ipin_inline = (int) ipin_option( 'ipin_social_inline', 5 );
				// Folding a single icon away would save nothing.
				if ( count( $ipin_links ) <= $ipin_inline + 1 ) {
					$ipin_inline = count( $ipin_links );
				}
				foreach ( array_slice( $ipin_links, 0, $ipin_inline ) as [ $ipin_url, $ipin_icon, $ipin_name ] ) : ?>
				<li>
					<a href="<?php echo esc_url( $ipin_url ); ?>"
					   class="topmenu-social"
					   aria-label="<?php echo esc_attr( sprintf( /* translators: %s = platform name */ __( '%s (opens in new tab)', 'ipin-modern' ), $ipin_name ) ); ?>"
					   target="_blank"
					   rel="me noopener noreferrer">
						<?php echo ipin_social_icon( $ipin_icon ); // phpcs:ignore WordPress.Security.EscapeOutput -- bundled SVG file ?>
					</a>
				</li>
				<?php endforeach; ?>

				<?php $ipin_more = array_slice( $ipin_links, $ipin_inline );
				if ( $ipin_more ) : ?>
				<li class="topmenu-social-more">
					<details>
						<summary class="topmenu-social"
						         aria-label="<?php echo esc_attr( sprintf( /* translators: %d = number of further profiles */ _n( '%d more profile', '%d more profiles', count( $ipin_more ), 'ipin-modern' ), count( $ipin_more ) ) ); ?>">
							<svg class="ipin-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
						</summary>
						<ul class="topmenu-social-more__panel" role="list">
							<?php foreach ( $ipin_more as [ $ipin_url, $ipin_icon, $ipin_name ] ) : ?>
							<li>
								<a href="<?php echo esc_url( $ipin_url ); ?>" target="_blank" rel="me noopener noreferrer">
									<?php echo ipin_social_icon( $ipin_icon ); // phpcs:ignore WordPress.Security.EscapeOutput -- bundled SVG file ?>
									<span><?php echo esc_html( $ipin_name ); ?></span>
									<span class="sr-only"><?php esc_html_e( '(opens in new tab)', 'ipin-modern' ); ?></span>
								</a>
							</li>
							<?php endforeach; ?>
						</ul>
					</details>
				</li>
				<?php endif; ?>

				</ul>

				<!-- Dark-mode toggle: sun/moon pill switch with sliding
				     gradient thumb (WCAG 4.1.2: aria-pressed + aria-label
				     updated by JS via the data-label-* attributes) -->
				<button id="dark-mode-toggle"
				        class="mode-switch"
				        aria-label="<?php esc_attr_e( 'Switch to dark mode', 'ipin-modern' ); ?>"
				        aria-pressed="false"
				        data-label-dark="<?php esc_attr_e( 'Switch to dark mode', 'ipin-modern' ); ?>"
				        data-label-light="<?php esc_attr_e( 'Switch to light mode', 'ipin-modern' ); ?>">
					<span class="mode-switch__thumb" aria-hidden="true"></span>
					<span class="mode-switch__icon mode-switch__icon--sun" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0-15a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 17a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1ZM2 12a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm17-1a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2ZM4.9 4.9a1 1 0 0 1 1.4 0l1.5 1.5a1 1 0 0 1-1.5 1.4L4.9 6.3a1 1 0 0 1 0-1.4Zm12.3 12.3a1 1 0 0 1 1.4 0l1.5 1.5a1 1 0 0 1-1.4 1.4l-1.5-1.5a1 1 0 0 1 0-1.4Zm1.5-12.3a1 1 0 0 1 0 1.4l-1.5 1.5a1 1 0 1 1-1.4-1.5l1.5-1.4a1 1 0 0 1 1.4 0ZM6.3 17.2a1 1 0 0 1 0 1.4l-1.4 1.5a1 1 0 0 1-1.5-1.4l1.5-1.5a1 1 0 0 1 1.4 0Z"/></svg>
					</span>
					<span class="mode-switch__icon mode-switch__icon--moon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79Z"/></svg>
					</span>
				</button>

			</div><!-- /.topmenu-social-wrap -->
		</div><!-- /#nav-main -->
	</div><!-- /.nav-inner -->
</nav><!-- /#topmenu -->

<?php
// Archive / search heading banner — uses <header> as a supplemental landmark
if ( is_search() || is_category() || is_tag() || is_archive() ) : ?>
<header class="subpage-title">
	<?php if ( is_search() ) : ?>
		<h1><?php printf(
			/* translators: %s = search query */
			esc_html__( 'Search results for "%s"', 'ipin-modern' ),
			'<em>' . esc_html( get_search_query() ) . '</em>'
		); ?></h1>
		<?php $ipin_found = (int) $GLOBALS['wp_query']->found_posts; ?>
		<p><?php printf(
			/* translators: %s = number of results */
			esc_html( _n( '%s pin found', '%s pins found', $ipin_found, 'ipin-modern' ) ),
			esc_html( number_format_i18n( $ipin_found ) )
		); ?></p>
	<?php elseif ( is_category() ) : ?>
		<h1><?php single_cat_title(); ?></h1>
		<?php $ipin_desc = category_description(); if ( $ipin_desc && ! is_wp_error( $ipin_desc ) ) echo '<p>' . wp_kses_post( $ipin_desc ) . '</p>'; ?>
	<?php elseif ( is_tag() ) : ?>
		<h1><?php printf(
			/* translators: %s = tag name */
			esc_html__( 'Tag: %s', 'ipin-modern' ),
			'<em>' . esc_html( single_tag_title( '', false ) ) . '</em>'
		); ?></h1>
		<?php if ( tag_description() ) echo '<p>' . wp_kses_post( tag_description() ) . '</p>'; ?>
	<?php elseif ( is_author() ) : ?>
		<h1><?php printf(
			/* translators: %s = author name */
			esc_html__( 'Posts by %s', 'ipin-modern' ),
			'<em>' . esc_html( get_the_author() ) . '</em>'
		); ?></h1>
	<?php elseif ( is_post_type_archive() ) : ?>
		<h1><?php post_type_archive_title(); ?></h1>
	<?php else : ?>
		<h1><?php the_archive_title(); ?></h1>
	<?php endif; ?>
</header>
<?php endif; ?>
