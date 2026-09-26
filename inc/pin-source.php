<?php
/**
 * iPin Modern — Pin source
 *
 * An editor box for _ipin_source_url, the link to where a pin came from.
 * The lightbox shows it as a "View source" link with the site's host name
 * (inc/rest-api.php sends it; assets/js/lightbox.js renders it).
 *
 * 5.0 kept reading the field but lost the box that set it; restored in 5.1
 * from 4.5's "iPin Details" box. The video half of that box lives on as
 * the "Video pin" box in inc/video.php.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

function ipin_source_add_meta_box(): void {
	add_meta_box(
		'ipin-pin-source',
		__( 'Pin source', 'ipin-modern' ),
		'ipin_source_render_meta_box',
		'post',
		'side'
	);
}
add_action( 'add_meta_boxes', 'ipin_source_add_meta_box' );

function ipin_source_render_meta_box( WP_Post $post ): void {
	$url = (string) get_post_meta( $post->ID, '_ipin_source_url', true );
	wp_nonce_field( 'ipin_source_save', 'ipin_source_nonce' );
	?>
	<p>
		<label for="ipin_source_url"><?php esc_html_e( 'Source URL', 'ipin-modern' ); ?></label>
		<input type="url" id="ipin_source_url" name="ipin_source_url" class="widefat"
			value="<?php echo esc_attr( $url ); ?>"
			placeholder="https://example.com/original-article">
	</p>
	<p class="description"><?php esc_html_e( 'Where this pin came from. Shown as a "View source" link in the lightbox. Leave blank for none.', 'ipin-modern' ); ?></p>
	<?php
}

function ipin_source_save_meta( int $post_id ): void {
	if ( ! isset( $_POST['ipin_source_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipin_source_nonce'] ) ), 'ipin_source_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url = esc_url_raw( trim( wp_unslash( (string) ( $_POST['ipin_source_url'] ?? '' ) ) ), [ 'http', 'https' ] );

	if ( '' === $url ) {
		delete_post_meta( $post_id, '_ipin_source_url' );
		return;
	}
	update_post_meta( $post_id, '_ipin_source_url', $url );
}
add_action( 'save_post', 'ipin_source_save_meta' );
