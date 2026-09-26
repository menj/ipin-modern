<?php
/**
 * iPin Modern — Sideblog post type (ipin_article)
 *
 * Articles are longer pieces that sit beside the pin grid: their own
 * "Sideblog" menu in the admin, an archive at /articles/ and single pages
 * at /article/{slug}/. The theme registers the post type itself, so the
 * Sideblog works as soon as the theme is active. Articles stay in the
 * database if the theme is ever switched, and reappear when it comes back.
 *
 * Also here, restored in 5.1 from 4.5:
 *   [ipin_sideblog]   shortcode listing recent articles, for any page
 *   Block pattern     "Sideblog: Latest Articles" (Query Loop on ipin_article)
 *   Redirects         4.5 served articles at /blog/{slug}/ and /blog/;
 *                     those addresses now 301 to /article/{slug}/ and
 *                     /articles/, so old links and search results keep working.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

function ipin_register_sideblog(): void {
	// The retired "iPin Sideblog" companion plugin registers the same post
	// type under the same name. If it is still active, let it; the settings
	// are identical.
	if ( post_type_exists( 'ipin_article' ) ) {
		return;
	}

	register_post_type( 'ipin_article', [
		'labels' => [
			'name'                  => __( 'Articles',               'ipin-modern' ),
			'singular_name'         => __( 'Article',                'ipin-modern' ),
			'add_new_item'          => __( 'Add New Article',        'ipin-modern' ),
			'edit_item'             => __( 'Edit Article',           'ipin-modern' ),
			'new_item'              => __( 'New Article',            'ipin-modern' ),
			'view_item'             => __( 'View Article',           'ipin-modern' ),
			'view_items'            => __( 'View Articles',          'ipin-modern' ),
			'search_items'          => __( 'Search Articles',        'ipin-modern' ),
			'not_found'             => __( 'No articles found.',     'ipin-modern' ),
			'not_found_in_trash'    => __( 'No articles found in Trash.', 'ipin-modern' ),
			'all_items'             => __( 'All Articles',           'ipin-modern' ),
			'archives'              => __( 'Article Archives',       'ipin-modern' ),
			'item_published'        => __( 'Article published.',     'ipin-modern' ),
			'item_updated'          => __( 'Article updated.',       'ipin-modern' ),
			'menu_name'             => __( 'Sideblog',               'ipin-modern' ),
		],
		'public'          => true,
		'rewrite'         => [ 'slug' => 'article', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive'     => 'articles',
		'menu_position'   => 5,
		'menu_icon'       => 'dashicons-text-page',
		'supports'        => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions' ],
		'show_in_rest'    => true,
	] );
}
add_action( 'init', 'ipin_register_sideblog' );

// New rewrite rules for /article/ and /articles/ on theme activation.
add_action( 'after_switch_theme', static function (): void {
	ipin_register_sideblog();
	flush_rewrite_rules();
} );


/* -------------------------------------------------------
   HAS ARTICLES
   True once at least one article is published. The header's
   fallback navigation uses it to add an "Articles" link, so
   the Sideblog is reachable before any menu is set up.
   ------------------------------------------------------- */
function ipin_has_articles(): bool {
	return post_type_exists( 'ipin_article' )
		&& (int) ( wp_count_posts( 'ipin_article' )->publish ?? 0 ) > 0;
}


/* -------------------------------------------------------
   RETIRED COMPANION PLUGIN
   5.0 pre-releases shipped the post type as a separate
   "iPin Sideblog" plugin. It is harmless but no longer
   needed; say so on the Plugins and iPin Settings screens.
   ------------------------------------------------------- */
function ipin_sideblog_plugin_notice(): void {
	if ( ! defined( 'IPIN_SIDEBLOG_LOADED' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, [ 'plugins', 'appearance_page_ipin-settings' ], true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		wp_kses(
			__( 'The Sideblog is now built into iPin Modern. You can deactivate and delete the <em>iPin Sideblog</em> plugin; your articles stay where they are.', 'ipin-modern' ),
			[ 'em' => [] ]
		)
	);
}
add_action( 'admin_notices', 'ipin_sideblog_plugin_notice' );


/* -------------------------------------------------------
   OLD /blog/ ADDRESSES (4.5)
   Only requests that would otherwise 404 are touched, so a
   real page or category at /blog/ is never overridden.
   ------------------------------------------------------- */
function ipin_sideblog_legacy_redirect(): void {
	if ( ! is_404() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only matched against a pattern
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( '' !== $home && str_starts_with( $path, $home . '/' ) ) {
		$path = substr( $path, strlen( $home ) + 1 );
	}

	if ( 'blog' === $path ) {
		wp_safe_redirect( (string) get_post_type_archive_link( 'ipin_article' ), 301 );
		exit;
	}

	if ( preg_match( '#^blog/([^/]+)$#', $path, $m ) ) {
		$article = get_page_by_path( sanitize_title( urldecode( $m[1] ) ), OBJECT, 'ipin_article' );
		if ( $article instanceof \WP_Post && 'publish' === $article->post_status ) {
			wp_safe_redirect( (string) get_permalink( $article ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'ipin_sideblog_legacy_redirect' );


/* -------------------------------------------------------
   [ipin_sideblog] SHORTCODE
   A compact list of recent articles, for any page or post.
     [ipin_sideblog]
     [ipin_sideblog count="5" title="From the Sideblog"]
   ------------------------------------------------------- */
function ipin_sideblog_shortcode( array|string $atts ): string {
	$a = shortcode_atts( [ 'count' => 5, 'title' => '' ], (array) $atts, 'ipin_sideblog' );

	$q = new \WP_Query( apply_filters( 'ipin_query_args', [
		'post_type'      => 'ipin_article',
		'posts_per_page' => max( 1, min( 20, (int) $a['count'] ) ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	] ) );

	if ( ! $q->have_posts() ) {
		return '';
	}

	ob_start();
	?>
	<div class="ipin-sideblog-widget">
		<?php if ( '' !== (string) $a['title'] ) : ?>
		<h3 class="ipin-sideblog-widget__title"><?php echo esc_html( (string) $a['title'] ); ?></h3>
		<?php endif; ?>
		<ul class="ipin-sideblog-widget__list" role="list">
			<?php while ( $q->have_posts() ) : $q->the_post(); ?>
			<li class="ipin-sideblog-widget__item">
				<?php if ( has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" class="ipin-sideblog-widget__thumb" tabindex="-1" aria-hidden="true">
					<?php the_post_thumbnail( 'thumbnail', [ 'alt' => '', 'loading' => 'lazy' ] ); ?>
				</a>
				<?php endif; ?>
				<div class="ipin-sideblog-widget__body">
					<a href="<?php the_permalink(); ?>" class="ipin-sideblog-widget__link"><?php the_title(); ?></a>
					<time class="ipin-sideblog-widget__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( ipin_human_time_diff( (int) get_post_time( 'U', true ) ) ); ?>
					</time>
				</div>
			</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ul>
		<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ipin_article' ) ); ?>" class="ipin-sideblog-widget__all">
			<?php esc_html_e( 'All articles', 'ipin-modern' ); ?>
		</a>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'ipin_sideblog', 'ipin_sideblog_shortcode' );


/* -------------------------------------------------------
   BLOCK PATTERN — "Sideblog: Latest Articles"
   A Query Loop on ipin_article, in the inserter under
   Patterns → iPin Modern. Core blocks only.
   Priority 11: the post type (and its archive link) is
   registered at 10.
   ------------------------------------------------------- */
function ipin_register_block_patterns(): void {
	register_block_pattern_category( 'ipin', [ 'label' => __( 'iPin Modern', 'ipin-modern' ) ] );

	$archive = (string) get_post_type_archive_link( 'ipin_article' );

	register_block_pattern( 'ipin/sideblog-latest', [
		'title'       => __( 'Sideblog: Latest Articles', 'ipin-modern' ),
		'description' => __( 'The five newest Sideblog articles with image, title and date.', 'ipin-modern' ),
		'categories'  => [ 'ipin', 'query' ],
		'keywords'    => [ 'sideblog', 'articles', 'list', 'editorial' ],
		'content'     => '<!-- wp:group {"className":"ipin-pattern-sideblog","layout":{"type":"constrained"}} -->
<div class="wp-block-group ipin-pattern-sideblog">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">' . esc_html__( 'From the Sideblog', 'ipin-modern' ) . '</h3>
<!-- /wp:heading -->
<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"ipin_article","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"layout":{"type":"default"}} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-columns is-not-stacked-on-mobile">
<!-- wp:column {"width":"80px"} -->
<div class="wp-block-column" style="flex-basis:80px">
<!-- wp:post-featured-image {"isLink":true,"width":"80px","height":"80px","scale":"cover","style":{"border":{"radius":"6px"}}} /-->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:post-title {"isLink":true,"level":4,"style":{"spacing":{"margin":{"top":"0","bottom":"4px"}}}} /-->
<!-- wp:post-date {"style":{"typography":{"fontSize":"0.8rem"}}} /-->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- /wp:post-template -->
<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'No articles yet.', 'ipin-modern' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
<!-- wp:paragraph {"align":"right","style":{"typography":{"fontSize":"0.85rem"}}} -->
<p class="has-text-align-right"><a href="' . esc_url( $archive ) . '">' . esc_html__( 'All articles', 'ipin-modern' ) . '</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
	] );
}
add_action( 'init', 'ipin_register_block_patterns', 11 );
