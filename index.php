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
		if ( $hero_title ) :
	?>
	<header class="home-hero">
		<h1 class="home-hero__title"><?php echo wp_kses( $hero_html, [ 'span' => [ 'class' => [] ] ] ); ?></h1>
		<?php if ( $hero_lede ) : ?>
		<p class="home-hero__lede"><?php echo wp_kses_post( $hero_lede ); ?></p>
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
		   href="<?php echo esc_url( home_url( '/' ) ); ?>"
		   aria-current="<?php echo ! $current_sort ? 'page' : 'false'; ?>">
			<?php esc_html_e( 'Latest', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === '7days' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( add_query_arg( 'popular', '7days', home_url( '/' ) ) ); ?>"
		   aria-current="<?php echo $current_sort === '7days' ? 'page' : 'false'; ?>">
			<i class="fa fa-fire-alt" aria-hidden="true"></i>
			<?php esc_html_e( 'Last 7 days', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === '30days' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( add_query_arg( 'popular', '30days', home_url( '/' ) ) ); ?>"
		   aria-current="<?php echo $current_sort === '30days' ? 'page' : 'false'; ?>">
			<i class="fa fa-chart-line" aria-hidden="true"></i>
			<?php esc_html_e( 'This month', 'ipin' ); ?>
		</a>
		<a class="sort-bar__btn<?php echo $current_sort === 'all' ? ' active' : ''; ?>"
		   href="<?php echo esc_url( add_query_arg( 'popular', 'all', home_url( '/' ) ) ); ?>"
		   aria-current="<?php echo $current_sort === 'all' ? 'page' : 'false'; ?>">
			<i class="fa fa-crown" aria-hidden="true"></i>
			<?php esc_html_e( 'All time', 'ipin' ); ?>
		</a>
	</nav>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>

		<div id="ajax-loader-masonry" aria-hidden="true" role="presentation">
			<div class="ajax-loader"></div>
		</div>

		<!-- aria-busy toggled by JS while masonry initialises -->
		<div id="masonry" aria-busy="true">
			<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'thumb' ); ?> data-post-id="<?php the_ID(); ?>">

				<!-- Thumbnail image — decorative duplicate of the title link;
				     hidden from AT to avoid announcing the same destination twice -->
				<a href="<?php the_permalink(); ?>"
				   class="thumb-img-wrap"
				   tabindex="-1"
				   aria-hidden="true"
				   focusable="false">
					<?php
					$img_src    = '';
					$img_width  = 0;
					$img_height = 0;

					if ( has_post_thumbnail() ) {
						$img_data   = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'medium' );
						$img_src    = $img_data[0] ?? '';
						$img_width  = $img_data[1] ?? 0;
						$img_height = $img_data[2] ?? 0;
						$has_image  = true;
					} elseif ( $post_images = get_children( [
						'post_parent'    => get_the_ID(),
						'post_type'      => 'attachment',
						'post_mime_type' => 'image',
						'numberposts'    => 1,
					] ) ) {
						$pi         = reset( $post_images );
						$img_data   = wp_get_attachment_image_src( $pi->ID, 'medium' );
						$img_src    = $img_data[0] ?? '';
						$img_width  = $img_data[1] ?? 0;
						$img_height = $img_data[2] ?? 0;
						$has_image  = true;
					} elseif ( preg_match( '/<img[^>]+src=["\']([^"\']+)/i', get_the_content(), $match ) ) {
						$img_src   = $match[1];
						$has_image = true;
					} else {
						$has_image = false;
					}

					$w = (int) get_option( 'ipin_card_width', 220 );
					$h = ( $img_width && $img_height ) ? (int) round( $w / $img_width * $img_height ) : $w;

					// No-image placeholder: first letter of title for the monogram
					$title_initial = mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) );
					?>

					<?php if ( $has_image ) : ?>
					<img
						src="<?php echo esc_url( $img_src ); ?>"
						alt="<?php the_title_attribute(); ?>"
						width="<?php echo esc_attr( $w ); ?>"
						height="<?php echo esc_attr( $h ); ?>"
						loading="lazy"
						decoding="async"
					>
					<?php else : ?>
					<!-- No-image placeholder: gradient panel with post title monogram -->
					<div class="thumb-no-image" aria-hidden="true">
						<span class="thumb-no-image__initial"><?php echo esc_html( $title_initial ); ?></span>
						<span class="thumb-no-image__icon">&#x1f4cc;</span>
					</div>
					<?php endif; ?>

					<!-- Hover/focus action bar — hidden from AT; keyboard users reach the
					     visible title link and the duplicate links below instead -->
					<div class="masonry-actionbar" aria-hidden="true">
						<a class="btn btn-comment"
						   href="<?php the_permalink(); ?>#respond"
						   tabindex="-1">
							<i class="fa fa-comment" aria-hidden="true"></i>
							<?php esc_html_e( 'Comment', 'ipin' ); ?>
						</a>
						<a class="btn btn-view"
						   href="<?php the_permalink(); ?>"
						   tabindex="-1">
							<?php esc_html_e( 'View', 'ipin' ); ?>
							<i class="fa fa-arrow-right" aria-hidden="true"></i>
						</a>
					</div>
				</a><!-- /.thumb-img-wrap -->

				<!-- Card body — the accessible entry point for keyboard/AT users -->
				<div class="thumb-body">

					<h2 class="thumbtitle">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>

					<?php
					$show_avatars    = get_option( 'show_avatars' );
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
					$fp_comments_num = (int) ipin_option( 'ipin_frontpage_comments', 3 );
					if ( $fp_comments_num > 0 ) :
						$comments = get_comments( [
							'number'  => $fp_comments_num,
							'post_id' => get_the_ID(),
							'status'  => 'approve',
						] );
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
