<?php get_header(); ?>

<!-- main landmark targets the skip link (WCAG 2.4.1) -->
<main id="main-content" tabindex="-1">
<div id="masonry-wrap">

	<!-- ── Homepage hero: statement heading + lede ───── -->
	<?php if ( ( is_home() || is_front_page() ) && ! is_paged() && (int) ipin_option( 'ipin_hero_enabled', 1 ) ) :
		$hero_title = trim( (string) ipin_option( 'ipin_hero_title', '' ) );
		$hero_lede  = trim( (string) ipin_option( 'ipin_hero_lede', '' ) );
		if ( '' === $hero_title ) {
			$hero_title = get_bloginfo( 'name', 'display' );
		}
		if ( '' === $hero_lede ) {
			$hero_lede = get_bloginfo( 'description', 'display' );
		}
		// *word* in the heading gets the brand-gradient accent.
		$hero_html = preg_replace(
			'/\*([^*]+)\*/',
			'<span class="home-hero__accent">$1</span>',
			esc_html( $hero_title )
		);
		// Bento panel: first sticky post, else the newest pin with a thumbnail.
		$bento_post = null;
		if ( (int) ipin_option( 'ipin_hero_bento', 1 ) ) {
			$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
			if ( $sticky ) {
				$bento_post = get_post( $sticky[0] );
			}
			if ( ! $bento_post || ! has_post_thumbnail( $bento_post ) ) {
				$latest     = get_posts( [ 'numberposts' => 1, 'meta_key' => '_thumbnail_id' ] );
				$bento_post = $latest ? $latest[0] : null;
			}
		}
		if ( $hero_title ) :
	?>
	<header class="home-hero<?php echo $bento_post ? ' home-hero--bento' : ''; ?>">
		<div class="home-hero__text">
			<h1 class="home-hero__title"><?php echo wp_kses( $hero_html, [ 'span' => [ 'class' => [] ] ] ); ?></h1>
			<?php if ( $hero_lede ) : ?>
			<p class="home-hero__lede"><?php echo wp_kses_post( $hero_lede ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $bento_post ) :
			$b_id    = $bento_post->ID;
			$b_cats  = get_the_category( $b_id );
			$b_com   = (int) get_comments_number( $b_id );
			$counts  = wp_count_posts();
			$n_pins  = (int) ( $counts->publish ?? 0 );
			$n_cats  = count( get_categories( [ 'hide_empty' => true ] ) );
			$n_coms  = (int) ( wp_count_comments()->approved ?? 0 );
			$chips   = get_categories( [ 'orderby' => 'count', 'order' => 'DESC', 'number' => 4, 'hide_empty' => true ] );
		?>
		<div class="home-hero__bento">
			<a class="bento-feature" href="<?php echo esc_url( get_permalink( $b_id ) ); ?>">
				<?php echo get_the_post_thumbnail( $b_id, 'large', [
					'class'         => 'bento-feature__img',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				] ); ?>
				<span class="bento-feature__meta">
					<strong><?php echo esc_html( get_the_title( $b_id ) ); ?></strong>
					<span>
						<?php esc_html_e( 'Featured', 'ipin' ); ?><?php
						if ( $b_cats ) { echo ' · ' . esc_html( $b_cats[0]->name ); }
						if ( $b_com ) {
							/* translators: %d = number of comments */
							echo ' · ' . esc_html( sprintf( _n( '%d comment', '%d comments', $b_com, 'ipin' ), $b_com ) );
						}
						?>
					</span>
				</span>
			</a>
			<div class="bento-side">
				<div class="bento-tile">
					<span class="bento-tile__label"><?php esc_html_e( 'This board', 'ipin' ); ?></span>
					<span class="bento-tile__stat"><?php echo esc_html( number_format_i18n( $n_pins ) ); ?> <small><?php esc_html_e( 'pins', 'ipin' ); ?></small></span>
					<p class="bento-tile__note">
						<?php printf(
							/* translators: 1: category count, 2: comment count */
							esc_html__( '%1$s categories · %2$s comments', 'ipin' ),
							esc_html( number_format_i18n( $n_cats ) ),
							esc_html( number_format_i18n( $n_coms ) )
						); ?>
					</p>
				</div>
				<?php if ( $chips ) : ?>
				<div class="bento-tile">
					<span class="bento-tile__label"><?php esc_html_e( 'Browse', 'ipin' ); ?></span>
					<div class="bento-tile__chips">
						<?php foreach ( $chips as $chip ) : ?>
						<a class="bento-chip" href="<?php echo esc_url( get_category_link( $chip ) ); ?>"><?php echo esc_html( $chip->name ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</header>
	<?php endif; endif; ?>

	<!-- ── Popular posts sort bar ────────────────────── -->
	<?php
	$current_sort = sanitize_key( $_GET['popular'] ?? '' );
	if ( is_home() || is_front_page() ) :
	?>
	<nav class="sort-bar" aria-label="<?php esc_attr_e( 'Sort posts', 'ipin' ); ?>">
		<a class="sort-bar__btn<?php echo ! $current_sort ? ' active' : ''; ?>"
		   href="<?php echo esc_url( ipin_popular_sort_url() ); ?>"
		   aria-current="<?php echo ! $current_sort ? 'page' : 'false'; ?>">
			<?php esc_html_e( 'Latest', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === '7days' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( ipin_popular_sort_url( '7days' ) ); ?>"
		   aria-current="<?php echo $current_sort === '7days' ? 'page' : 'false'; ?>">
			<?php echo ipin_icon( 'fire' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php esc_html_e( 'Last 7 days', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === '30days' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( ipin_popular_sort_url( '30days' ) ); ?>"
		   aria-current="<?php echo $current_sort === '30days' ? 'page' : 'false'; ?>">
			<?php echo ipin_icon( 'chart-line' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php esc_html_e( 'This month', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === 'all' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( ipin_popular_sort_url( 'all' ) ); ?>"
		   aria-current="<?php echo $current_sort === 'all' ? 'page' : 'false'; ?>">
			<?php echo ipin_icon( 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php esc_html_e( 'All time', 'ipin' ); ?>
		</a>
	</nav>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>

		<!-- aria-busy cleared by ipin.grid.js after first layout -->
		<div id="masonry" aria-busy="true">
			<?php
			$fp_comments_num = (int) ipin_option( 'ipin_frontpage_comments', 3 );
			$ipin_previews   = ipin_comment_previews( $GLOBALS['wp_query']->posts, $fp_comments_num );
			?>
			<?php while ( have_posts() ) : the_post(); ?>

			<?php $is_video_pin = (bool) ipin_post_video( get_the_ID() ); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( $is_video_pin ? 'thumb thumb--video' : 'thumb' ); ?> data-post-id="<?php the_ID(); ?>">

				<!-- Thumbnail image — decorative duplicate of the title link;
				     hidden from AT to avoid announcing the same destination twice -->
				<a href="<?php the_permalink(); ?>"
				   class="thumb-img-wrap"
				   tabindex="-1"
				   aria-hidden="true"
				   focusable="false">
					<?php
					$w        = ipin_sanitize_card_width( get_option( 'ipin_card_width', 220 ) );
					$img_id   = ipin_card_image_id( get_post() );
					$img_html = '';
					if ( $img_id ) {
						// ipin-card + srcset: the browser picks the file that fits the
						// card at the screen's density instead of one fixed 'medium'.
						$img_html = wp_get_attachment_image( $img_id, 'ipin-card', false, [
							'alt'      => the_title_attribute( [ 'echo' => false ] ),
							'sizes'    => "(max-width: 420px) calc(100vw - 48px), {$w}px",
							'loading'  => 'lazy',
							'decoding' => 'async',
							'style'    => 'view-transition-name: ipin-media-' . get_the_ID(),
						] );
					} elseif ( preg_match( '/<img[^>]+src=["\']([^"\']+)/i', get_the_content(), $match ) ) {
						// Last resort: an image hotlinked in the content (size unknown, so square).
						$img_html = sprintf(
							'<img src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" style="view-transition-name: ipin-media-%4$d">',
							esc_url( $match[1] ),
							the_title_attribute( [ 'echo' => false ] ),
							$w,
							get_the_ID()
						);
					}

					// No-image placeholder: first letter of title for the monogram
					$title_initial = mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) );
					?>

					<?php if ( $img_html ) : ?>
					<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built or escaped above ?>
					<?php else : ?>
					<!-- No-image placeholder: gradient panel with post title monogram -->
					<div class="thumb-no-image" aria-hidden="true">
						<span class="thumb-no-image__initial"><?php echo esc_html( $title_initial ); ?></span>
						<span class="thumb-no-image__icon">&#x1f4cc;</span>
					</div>
					<?php endif; ?>

					<?php if ( $is_video_pin ) : ?>
					<span class="thumb-play" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.86l11-6.86a1 1 0 0 0 0-1.72l-11-6.86A1 1 0 0 0 8 5.14Z"/></svg>
					</span>
					<?php endif; ?>

					<!-- Hover/focus action bar — decorative (aria-hidden, no pointer
					     events). Spans, not links: an <a> nested in the wrapping <a>
					     is invalid HTML and makes browsers restructure the card. -->
					<div class="masonry-actionbar" aria-hidden="true">
						<span class="btn btn-comment">
							<?php echo ipin_icon( 'comment' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php esc_html_e( 'Comment', 'ipin' ); ?>
						</span>
						<span class="btn btn-view">
							<?php esc_html_e( 'View', 'ipin' ); ?>
							<?php echo ipin_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</div>
				</a><!-- /.thumb-img-wrap -->

				<!-- Card body — the accessible entry point for keyboard/AT users -->
				<div class="thumb-body">

					<h2 class="thumbtitle">
						<a href="<?php the_permalink(); ?>" aria-keyshortcuts="Shift+Enter"><?php the_title(); ?><?php
							if ( $is_video_pin ) {
								echo ' <span class="screen-reader-text">' . esc_html__( '(video)', 'ipin' ) . '</span>';
							}
						?></a>
					</h2>

					<?php
					// Needs both core's Settings > Discussion switch and the theme's own.
					$show_avatars    = get_option( 'show_avatars' ) && (int) ipin_option( 'ipin_show_avatars_grid', 1 );
					$comments_number = get_comments_number();
					?>
					<div class="masonry-meta<?php echo ( ! $comments_number && ! $show_avatars ) ? ' text-center' : ''; ?>">
						<?php if ( $show_avatars ) : ?>
							<div class="masonry-meta-avatar" aria-hidden="true">
								<?php echo get_avatar( get_the_author_meta( 'user_email' ), 24, '', get_the_author() ); ?>
							</div>
						<?php endif; ?>
						<div>
							<span class="masonry-meta-author"><?php the_author(); ?></span>
							<?php esc_html_e( 'in', 'ipin' ); ?>
							<span class="masonry-meta-content"><?php the_category( ', ' ); ?></span>
						</div>
					</div>

					<!-- Frontpage comments preview -->
					<?php
					if ( $fp_comments_num > 0 ) :
						$comments = $ipin_previews[ get_the_ID() ] ?? [];
						foreach ( $comments as $comment ) :
					?>
						<div class="masonry-meta">
							<?php if ( $show_avatars ) : ?>
								<div class="masonry-meta-avatar" aria-hidden="true">
									<?php echo get_avatar( $comment->comment_author_email, 24, '', esc_attr( $comment->comment_author ) ); ?>
								</div>
							<?php endif; ?>
							<div>
								<span class="masonry-meta-author"><?php echo esc_html( $comment->comment_author ); ?></span>
								<?php echo esc_html( wp_trim_words( $comment->comment_content, 12 ) ); ?>
							</div>
						</div>
					<?php endforeach;

					if ( $comments_number > $fp_comments_num ) : ?>
						<div class="masonry-meta text-center">
							<a href="<?php the_permalink(); ?>#comments">
								<?php printf(
									esc_html( _n( 'View all %d comment', 'View all %d comments', $comments_number, 'ipin' ) ),
									$comments_number
								); ?>
							</a>
						</div>
					<?php endif; endif; ?>

				</div><!-- /.thumb-body -->
			</article><!-- /.thumb -->

			<?php endwhile; ?>
		</div><!-- /#masonry -->

		<!-- Pagination — landmark nav with distinct label (WCAG 2.4.6) -->
		<nav id="navigation" aria-label="<?php esc_attr_e( 'Posts pagination', 'ipin' ); ?>">
			<ul class="pager" role="list">
				<li id="navigation-next">
					<?php next_posts_link( '<span aria-hidden="true">&laquo;</span> ' . esc_html__( 'Older posts', 'ipin' ) ); ?>
				</li>
				<li id="navigation-previous">
					<?php previous_posts_link( esc_html__( 'Newer posts', 'ipin' ) . ' <span aria-hidden="true">&raquo;</span>' ); ?>
				</li>
			</ul>
		</nav>

	<?php else : ?>

		<section class="empty-state" aria-label="<?php esc_attr_e( 'No content found', 'ipin' ); ?>">
			<span class="empty-icon" aria-hidden="true">📌</span>
			<h1><?php esc_html_e( 'Nothing pinned here yet', 'ipin' ); ?></h1>
			<p><?php esc_html_e( 'Perhaps searching will help.', 'ipin' ); ?></p>
			<?php get_search_form(); ?>
		</section>

	<?php endif; ?>
</div><!-- /#masonry-wrap -->
</main>

<?php get_footer(); ?>
