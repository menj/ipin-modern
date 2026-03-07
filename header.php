<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#FF3CAC">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Skip to main content (WCAG 2.4.1) -->
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to main content', 'ipin' ); ?></a>

<noscript>
	<style>#masonry { visibility: visible !important; }</style>
</noscript>

<!-- =======================================================
     TOP NAVIGATION  (landmark: <nav>, WCAG 1.3.6 / 4.1.2)
     ======================================================= -->
<nav id="topmenu" aria-label="<?php esc_attr_e( 'Main navigation', 'ipin' ); ?>">
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
		   		__( '%s — go to homepage', 'ipin' ),
		   		get_bloginfo( 'name' )
		   ) ); ?>">
			<span aria-hidden="true"><?php bloginfo( 'name' ); ?></span>
		</a>
		<?php endif; ?>

		<!-- Hamburger (WCAG 4.1.2: name, role, value — aria-expanded updated by JS) -->
		<button class="navbar-toggle"
		        aria-label="<?php esc_attr_e( 'Open navigation menu', 'ipin' ); ?>"
		        aria-expanded="false"
		        aria-controls="nav-main">
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
					'items_wrap'     => '<ul id="%1$s" class="%2$s" role="list">%3$s</ul>',
				] ); ?>
			<?php else : ?>
				<ul class="nav-list" role="list">
					<?php wp_list_pages( [ 'title_li' => '', 'depth' => 1, 'sort_column' => 'menu_order' ] ); ?>
				</ul>
			<?php endif; ?>

			<!-- Inline search (WCAG 1.3.1: <label>, role="search") -->
			<form class="nav-search"
			      method="get"
			      action="<?php echo esc_url( home_url( '/' ) ); ?>"
			      role="search"
			      aria-label="<?php esc_attr_e( 'Site search', 'ipin' ); ?>">
				<label for="nav-search-input" class="sr-only">
					<?php esc_html_e( 'Search', 'ipin' ); ?>
				</label>
				<input
					type="search"
					id="nav-search-input"
					name="s"
					placeholder="<?php esc_attr_e( 'Search…', 'ipin' ); ?>"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					autocomplete="off"
				>
				<button type="submit">
					<i class="fa fa-search" aria-hidden="true"></i>
					<span class="sr-only"><?php esc_html_e( 'Search', 'ipin' ); ?></span>
				</button>
			</form>

			<!-- Social links (WCAG 2.4.6: each has a descriptive label) -->
			<div class="topmenu-social-wrap"
			     role="list"
			     aria-label="<?php esc_attr_e( 'Social links', 'ipin' ); ?>">

				<?php if ( (int) ipin_option( 'ipin_rss_visible', 1 ) ) : ?>
				<a href="<?php bloginfo( 'rss2_url' ); ?>"
				   class="topmenu-social"
				   aria-label="<?php esc_attr_e( 'RSS feed (opens in new tab)', 'ipin' ); ?>"
				   role="listitem"
				   target="_blank"
				   rel="noopener">
					<i class="fa fa-rss" aria-hidden="true"></i>
				</a>
				<?php endif; ?>

				<?php $twitter = ipin_option( 'ipin_twitter_url' ); if ( $twitter ) : ?>
				<a href="<?php echo esc_url( $twitter ); ?>"
				   class="topmenu-social"
				   aria-label="<?php esc_attr_e( 'Follow us on X / Twitter (opens in new tab)', 'ipin' ); ?>"
				   role="listitem"
				   target="_blank"
				   rel="noopener noreferrer">
					<i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
				</a>
				<?php endif; ?>

				<?php $facebook = ipin_option( 'ipin_facebook_url' ); if ( $facebook ) : ?>
				<a href="<?php echo esc_url( $facebook ); ?>"
				   class="topmenu-social"
				   aria-label="<?php esc_attr_e( 'Follow us on Facebook (opens in new tab)', 'ipin' ); ?>"
				   role="listitem"
				   target="_blank"
				   rel="noopener noreferrer">
					<i class="fa-brands fa-facebook" aria-hidden="true"></i>
				</a>
				<?php endif; ?>

				<?php $instagram = ipin_option( 'ipin_instagram_url' ); if ( $instagram ) : ?>
				<a href="<?php echo esc_url( $instagram ); ?>"
				   class="topmenu-social"
				   aria-label="<?php esc_attr_e( 'Follow us on Instagram (opens in new tab)', 'ipin' ); ?>"
				   role="listitem"
				   target="_blank"
				   rel="noopener noreferrer">
					<i class="fa-brands fa-instagram" aria-hidden="true"></i>
				</a>
				<?php endif; ?>

				<!-- Dark-mode toggle (WCAG 4.1.2: aria-pressed updated by JS) -->
				<button id="dark-mode-toggle"
				        aria-label="<?php esc_attr_e( 'Switch to dark mode', 'ipin' ); ?>"
				        aria-pressed="false">
					<i class="fa fa-moon" aria-hidden="true"></i>
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
			esc_html__( 'Search results for "%s"', 'ipin' ),
			'<em>' . esc_html( get_search_query() ) . '</em>'
		); ?></h1>
		<?php if ( category_description() ) echo '<p>' . wp_kses_post( category_description() ) . '</p>'; ?>
	<?php elseif ( is_category() ) : ?>
		<h1><?php single_cat_title(); ?></h1>
		<?php if ( category_description() ) echo '<p>' . wp_kses_post( category_description() ) . '</p>'; ?>
	<?php elseif ( is_tag() ) : ?>
		<h1><?php printf(
			/* translators: %s = tag name */
			esc_html__( 'Tag: %s', 'ipin' ),
			'<em>' . single_tag_title( '', false ) . '</em>'
		); ?></h1>
		<?php if ( tag_description() ) echo '<p>' . wp_kses_post( tag_description() ) . '</p>'; ?>
	<?php elseif ( is_author() ) : ?>
		<h1><?php printf(
			/* translators: %s = author name */
			esc_html__( 'Posts by %s', 'ipin' ),
			'<em>' . esc_html( get_the_author() ) . '</em>'
		); ?></h1>
	<?php else : ?>
		<h1><?php the_archive_title(); ?></h1>
	<?php endif; ?>
</header>
<?php endif; ?>
