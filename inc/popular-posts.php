<?php
/**
 * iPin Modern — Popular Posts
 *
 * Ranks pins by comment count (cached in post_meta for fast ordering).
 * Provides iPin_Popular_Posts_Widget for the sidebar.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* -------------------------------------------------------
   QUERY POPULAR POSTS
   $period: 'all' | '7days' | '30days'
   ------------------------------------------------------- */
function ipin_get_popular_posts( int $limit = 12, string $period = 'all' ): \WP_Query {
	$args = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'comment_count',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	];

	if ( $period !== 'all' ) {
		$days = ( $period === '7days' ) ? 7 : 30;
		$args['date_query'] = [ [
			'after'     => gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
			'inclusive' => true,
		] ];
	}

	return new \WP_Query( $args );
}


/* -------------------------------------------------------
   SORT BAR URL HELPER
   ------------------------------------------------------- */
function ipin_popular_sort_url( string $period ): string {
	return add_query_arg( 'popular', $period, home_url( '/' ) );
}


/* -------------------------------------------------------
   PRE_GET_POSTS: honour ?popular= on the front page
   ------------------------------------------------------- */
add_action( 'pre_get_posts', static function ( \WP_Query $q ): void {
	if ( ! $q->is_main_query() || is_admin() ) return;
	if ( ! $q->is_home() && ! $q->is_front_page() ) return;

	$period = sanitize_key( $_GET['popular'] ?? '' );
	if ( ! in_array( $period, [ '7days', '30days', 'all' ], true ) ) return;

	$q->set( 'orderby', 'comment_count' );
	$q->set( 'order',   'DESC' );

	if ( $period !== 'all' ) {
		$days = ( $period === '7days' ) ? 7 : 30;
		$q->set( 'date_query', [ [
			'after'     => gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
			'inclusive' => true,
		] ] );
	}
} );


/* -------------------------------------------------------
   SIDEBAR WIDGET
   ------------------------------------------------------- */
class iPin_Popular_Posts_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'ipin_popular_posts',
			__( 'iPin: Popular Posts', 'ipin' ),
			[ 'description' => __( 'Display most-commented pins.', 'ipin' ) ]
		);
	}

	/** @param array<string,mixed> $args @param array<string,mixed> $instance */
	public function widget( $args, $instance ): void {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Popular Pins', 'ipin' );
		$number = (int) ( $instance['number'] ?? 5 );
		$period = sanitize_key( $instance['period'] ?? 'all' );

		echo wp_kses_post( $args['before_widget'] );
		echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );

		$q = ipin_get_popular_posts( $number, $period );
		if ( $q->have_posts() ) {
			echo '<ul class="ipin-popular-list">';
			while ( $q->have_posts() ) {
				$q->the_post();
				echo '<li class="ipin-popular-item">';
				if ( has_post_thumbnail() ) {
					echo '<a href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">';
					the_post_thumbnail( [ 48, 48 ], [ 'alt' => '' ] );
					echo '</a>';
				}
				echo '<div class="ipin-popular-info">'
					. '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>'
					. '<span class="ipin-popular-meta">'
						. '<i class="fa fa-comment" aria-hidden="true"></i> '
						. esc_html( number_format_i18n( (int) get_comments_number() ) )
					. '</span>'
					. '</div>';
				echo '</li>';
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'No posts found.', 'ipin' ) . '</p>';
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/** @param array<string,mixed> $instance */
	public function form( $instance ): void {
		$title  = esc_attr( $instance['title']  ?? __( 'Popular Pins', 'ipin' ) );
		$number = (int) ( $instance['number'] ?? 5 );
		$period = $instance['period'] ?? 'all';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'ipin' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				<?php esc_html_e( 'Number of posts:', 'ipin' ); ?>
			</label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
			       type="number" min="1" max="20" value="<?php echo esc_attr( $number ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>">
				<?php esc_html_e( 'Period:', 'ipin' ); ?>
			</label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'period' ) ); ?>">
				<option value="all"    <?php selected( $period, 'all' ); ?>><?php esc_html_e( 'All time', 'ipin' ); ?></option>
				<option value="30days" <?php selected( $period, '30days' ); ?>><?php esc_html_e( 'Last 30 days', 'ipin' ); ?></option>
				<option value="7days"  <?php selected( $period, '7days' ); ?>><?php esc_html_e( 'Last 7 days', 'ipin' ); ?></option>
			</select>
		</p>
		<?php
	}

	/** @param array<string,mixed> $new_instance @param array<string,mixed> $old_instance */
	public function update( $new_instance, $old_instance ): array {
		return [
			'title'  => sanitize_text_field( $new_instance['title'] ?? '' ),
			'number' => max( 1, (int) ( $new_instance['number'] ?? 5 ) ),
			'period' => in_array( $new_instance['period'] ?? '', [ 'all', '7days', '30days' ], true )
			            ? $new_instance['period'] : 'all',
		];
	}
}

add_action( 'widgets_init', static function (): void {
	register_widget( 'iPin_Popular_Posts_Widget' );
} );
