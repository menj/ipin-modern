<?php if ( post_password_required() ) return; ?>

<section id="comments" aria-label="<?php esc_attr_e( 'Comments', 'ipin-modern' ); ?>">

	<?php if ( have_comments() ) : ?>

		<h2 class="comments-title">
			<?php
			$count = get_comments_number();
			printf(
				/* translators: 1: number of comments, 2: post title */
				esc_html( _n( '%1$s comment on "%2$s"', '%1$s comments on "%2$s"', $count, 'ipin-modern' ) ),
				number_format_i18n( $count ),
				'<em>' . esc_html( get_the_title() ) . '</em>'
			);
			?>
		</h2>

		<ol class="commentlist">
			<?php wp_list_comments( [ 'callback' => 'ipin_comment', 'style' => 'ol' ] ); ?>
		</ol>

		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<nav aria-label="<?php esc_attr_e( 'Comment pages', 'ipin-modern' ); ?>">
			<ul class="pager" role="list">
				<li class="previous"><?php previous_comments_link( '<span aria-hidden="true">&laquo;</span> ' . esc_html__( 'Older Comments', 'ipin-modern' ) ); ?></li>
				<li class="next"><?php next_comments_link( esc_html__( 'Newer Comments', 'ipin-modern' ) . ' <span aria-hidden="true">&raquo;</span>' ); ?></li>
			</ul>
		</nav>
		<?php endif; ?>

	<?php endif; // have_comments() ?>

	<?php
	comment_form( [
		// One correctly levelled heading: core wraps the title in these tags
		// (its default is an <h3>, which the old nested <h2> sat inside).
		'title_reply_before'   => '<h2 id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</h2>',
		'title_reply'          => esc_html__( 'Leave a Comment', 'ipin-modern' ),
		/* translators: %s = name of the comment author being replied to */
		'title_reply_to'       => esc_html__( 'Reply to %s', 'ipin-modern' ),
		'cancel_reply_link'    => esc_html__( 'Cancel reply', 'ipin-modern' ),
		'label_submit'         => esc_html__( 'Post Comment', 'ipin-modern' ),
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		'comment_field'        =>
			'<div>'
			. '<label for="comment">' . esc_html__( 'Comment', 'ipin-modern' ) . ' <span aria-hidden="true">*</span><span class="sr-only">' . esc_html__( 'required', 'ipin-modern' ) . '</span></label>'
			. '<textarea id="comment" name="comment" rows="8" aria-required="true" required></textarea>'
			. '</div>',
	] );
	?>

</section>
