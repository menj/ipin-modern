<?php
/**
 * Homepage hero: the statement heading and lede from Settings → Layout,
 * plus the optional featured-pin panel. index.php loads it on the first
 * page of the blog home while the hero is switched on.
 */

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
// Bento panel: first sticky post with a thumbnail, else the newest pin
// with one. Both go through get_posts() so only published posts without
// a password qualify; sticky_posts keeps IDs of posts later made
// private, draft or protected, and those must not surface here.
$bento_post = null;
if ( (int) ipin_option( 'ipin_hero_bento', 1 ) ) {
	$bento_args = [
		'numberposts'  => 1,
		'post_status'  => 'publish',
		'has_password' => false,
		'meta_key'     => '_thumbnail_id',
	];
	$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts' ) ) );
	$found  = $sticky ? get_posts( $bento_args + [ 'post__in' => $sticky, 'orderby' => 'post__in' ] ) : [];
	if ( ! $found ) {
		$found = get_posts( $bento_args );
	}
	$bento_post = $found[0] ?? null;
}
if ( $hero_title ) :
?>
<!-- ── Homepage hero: statement heading + lede ───── -->
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
					<?php esc_html_e( 'Featured', 'ipin-modern' ); ?><?php
					if ( $b_cats ) { echo ' · ' . esc_html( $b_cats[0]->name ); }
					if ( $b_com ) {
						/* translators: %d = number of comments */
						echo ' · ' . esc_html( sprintf( _n( '%d comment', '%d comments', $b_com, 'ipin-modern' ), $b_com ) );
					}
					?>
				</span>
			</span>
		</a>
		<div class="bento-side">
			<div class="bento-tile">
				<span class="bento-tile__label"><?php esc_html_e( 'This board', 'ipin-modern' ); ?></span>
				<span class="bento-tile__stat"><?php echo esc_html( number_format_i18n( $n_pins ) ); ?> <small><?php esc_html_e( 'pins', 'ipin-modern' ); ?></small></span>
				<p class="bento-tile__note">
					<?php printf(
						/* translators: 1: category count, 2: comment count */
						esc_html__( '%1$s categories · %2$s comments', 'ipin-modern' ),
						esc_html( number_format_i18n( $n_cats ) ),
						esc_html( number_format_i18n( $n_coms ) )
					); ?>
				</p>
			</div>
			<?php if ( $chips ) : ?>
			<div class="bento-tile">
				<span class="bento-tile__label"><?php esc_html_e( 'Browse', 'ipin-modern' ); ?></span>
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
<?php endif; ?>
