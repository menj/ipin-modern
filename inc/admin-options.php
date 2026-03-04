<?php
/**
 * iPin Modern — Tabbed Admin Options Page
 * Registers the settings, renders the page, handles AJAX save.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------
   REGISTER SETTINGS
   ------------------------------------------------------- */
function ipin_register_settings(): void {
	$settings = [
		// General
		'ipin_frontpage_comments' => [ 'absint',      3    ],
		'ipin_posts_per_page'     => [ 'absint',      12   ],
		// Appearance
		'ipin_colour_scheme'      => [ 'sanitize_key', 'vivid' ],
		'ipin_dark_mode_default'  => [ 'absint',       0   ],
		'ipin_card_width'         => [ 'absint',       220 ],
		'ipin_rounded_cards'      => [ 'absint',       1   ],
		// Social links
		'ipin_twitter_url'        => [ 'esc_url_raw',  ''  ],
		'ipin_facebook_url'       => [ 'esc_url_raw',  ''  ],
		'ipin_instagram_url'      => [ 'esc_url_raw',  ''  ],
		'ipin_rss_visible'        => [ 'absint',       1   ],
		// Layout
		'ipin_show_avatars_grid'  => [ 'absint',       1   ],
		'ipin_sidebar_position'   => [ 'sanitize_key', 'right' ],
		'ipin_footer_text'        => [ 'wp_kses_post', ''  ],
	];

	foreach ( $settings as $key => [ $sanitize, $default ] ) {
		register_setting( 'ipin_options_group', $key, [
			'sanitize_callback' => $sanitize,
			'default'           => $default,
		] );
	}
}
add_action( 'admin_init', 'ipin_register_settings' );


/* -------------------------------------------------------
   AJAX SAVE HANDLER
   ------------------------------------------------------- */
function ipin_ajax_save_options(): void {
	check_ajax_referer( 'ipin_save_options', 'ipin_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized', 403 );
	}

	$fields = [
		'ipin_frontpage_comments'    => 'absint',
		'ipin_posts_per_page'        => 'absint',
		'ipin_colour_scheme'         => 'sanitize_key',
		'ipin_dark_mode_default'     => 'absint',
		'ipin_card_width'            => 'absint',
		'ipin_rounded_cards'         => 'absint',
		'ipin_twitter_url'           => 'esc_url_raw',
		'ipin_facebook_url'          => 'esc_url_raw',
		'ipin_instagram_url'         => 'esc_url_raw',
		'ipin_rss_visible'           => 'absint',
		'ipin_show_avatars_grid'     => 'absint',
		'ipin_sidebar_position'      => 'sanitize_key',
		'ipin_footer_text'           => 'wp_kses_post',
	];

	foreach ( $fields as $key => $cb ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above
		$val = isset( $_POST[ $key ] ) ? call_user_func( $cb, wp_unslash( $_POST[ $key ] ) ) : 0;
		update_option( $key, $val );
	}

	wp_send_json_success( [ 'message' => __( 'Settings saved.', 'ipin' ) ] );
}
add_action( 'wp_ajax_ipin_save_options', 'ipin_ajax_save_options' );


/* -------------------------------------------------------
   ADMIN MENU
   ------------------------------------------------------- */
function ipin_admin_menu(): void {
	add_theme_page(
		__( 'iPin Settings', 'ipin' ),
		__( 'iPin Settings', 'ipin' ),
		'manage_options',
		'ipin-settings',
		'ipin_render_settings_page'
	);
}
add_action( 'admin_menu', 'ipin_admin_menu' );


/* -------------------------------------------------------
   ENQUEUE ADMIN ASSETS
   ------------------------------------------------------- */
function ipin_admin_scripts( string $hook ): void {
	if ( 'appearance_page_ipin-settings' !== $hook ) return;

	wp_enqueue_style(
		'ipin-admin-css',
		get_template_directory_uri() . '/assets/css/admin.css',
		[],
		'3.0'
	);

	wp_enqueue_script(
		'ipin-admin-js',
		get_template_directory_uri() . '/assets/js/ipin.admin.js',
		[],
		'3.0',
		true
	);

	wp_localize_script( 'ipin-admin-js', 'ipinAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ipin_save_options' ),
		'saving'  => __( 'Saving…', 'ipin' ),
	] );
}
add_action( 'admin_enqueue_scripts', 'ipin_admin_scripts' );


/* -------------------------------------------------------
   HELPER — get option with default
   ------------------------------------------------------- */
function ipin_get( string $key, mixed $default = '' ): mixed {
	return get_option( $key, $default );
}


/* -------------------------------------------------------
   RENDER THE PAGE
   ------------------------------------------------------- */
function ipin_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$tabs = [
		'general'    => [ 'icon' => '⚙️',  'label' => __( 'General',    'ipin' ) ],
		'appearance' => [ 'icon' => '🎨',  'label' => __( 'Appearance', 'ipin' ) ],
		'social'     => [ 'icon' => '🔗',  'label' => __( 'Social',     'ipin' ) ],
		'ads'        => [ 'icon' => '💰',  'label' => __( 'Ads',        'ipin' ) ],
		'layout'     => [ 'icon' => '📐',  'label' => __( 'Layout',     'ipin' ) ],
	];

	$schemes = [
		'vivid'  => [ 'label' => 'Vivid',  'class' => 'swatch-vivid'  ],
		'ocean'  => [ 'label' => 'Ocean',  'class' => 'swatch-ocean'  ],
		'ember'  => [ 'label' => 'Ember',  'class' => 'swatch-ember'  ],
		'forest' => [ 'label' => 'Forest', 'class' => 'swatch-forest' ],
		'mono'   => [ 'label' => 'Mono',   'class' => 'swatch-mono'   ],
	];

	$current_scheme = ipin_get( 'ipin_colour_scheme', 'vivid' );
	?>
	<div id="ipin-settings-wrap">

		<!-- Header -->
		<div class="ipin-admin-header">
			<span class="ipin-admin-logo">📌 iPin Modern</span>
			<span class="ipin-admin-version">v3.0</span>
		</div>

		<!-- Tab navigation -->
		<ul class="ipin-tabs-nav" role="tablist">
			<?php foreach ( $tabs as $slug => $tab ) : ?>
			<li role="presentation">
				<button
					data-tab="<?php echo esc_attr( $slug ); ?>"
					role="tab"
					aria-selected="<?php echo $slug === 'general' ? 'true' : 'false'; ?>"
					aria-controls="ipin-tab-<?php echo esc_attr( $slug ); ?>"
					class="<?php echo $slug === 'general' ? 'active' : ''; ?>"
				>
					<span class="tab-icon" aria-hidden="true"><?php echo $tab['icon']; ?></span>
					<?php echo esc_html( $tab['label'] ); ?>
				</button>
			</li>
			<?php endforeach; ?>
		</ul>

		<form id="ipin-settings-form" method="post" action="">
			<?php wp_nonce_field( 'ipin_save_options', 'ipin_nonce' ); ?>
			<input type="hidden" name="action" value="ipin_save_options">

			<!-- ═══ TAB: GENERAL ═══ -->
			<div id="ipin-tab-general" class="ipin-tab-panel active" role="tabpanel">

				<p class="ipin-info-box">
					<strong><?php esc_html_e( 'General settings', 'ipin' ); ?></strong> —
					<?php esc_html_e( 'Control how posts appear on your grid homepage.', 'ipin' ); ?>
				</p>

				<div class="ipin-section-title"><?php esc_html_e( 'Homepage Grid', 'ipin' ); ?></div>

				<div class="ipin-field">
					<label for="ipin_frontpage_comments">
						<?php esc_html_e( 'Comments per card', 'ipin' ); ?>
					</label>
					<div>
						<input
							type="number"
							id="ipin_frontpage_comments"
							name="ipin_frontpage_comments"
							value="<?php echo esc_attr( ipin_get( 'ipin_frontpage_comments', 3 ) ); ?>"
							min="0" max="10"
						>
						<p class="ipin-field-desc">
							<?php esc_html_e( 'Number of recent comments to show on each card. Set to 0 to hide.', 'ipin' ); ?>
						</p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_posts_per_page">
						<?php esc_html_e( 'Posts per page', 'ipin' ); ?>
					</label>
					<div>
						<input
							type="number"
							id="ipin_posts_per_page"
							name="ipin_posts_per_page"
							value="<?php echo esc_attr( ipin_get( 'ipin_posts_per_page', 12 ) ); ?>"
							min="1" max="100"
						>
						<p class="ipin-field-desc">
							<?php esc_html_e( 'How many pins to load before infinite scroll fetches the next batch.', 'ipin' ); ?>
						</p>
					</div>
				</div>

				<div class="ipin-section-title"><?php esc_html_e( 'Avatars', 'ipin' ); ?></div>

				<div class="ipin-toggle-row">
					<label class="ipin-toggle">
						<input type="hidden" name="ipin_show_avatars_grid" value="0">
						<input
							type="checkbox"
							name="ipin_show_avatars_grid"
							value="1"
							<?php checked( 1, (int) ipin_get( 'ipin_show_avatars_grid', 1 ) ); ?>
						>
						<span class="ipin-toggle-slider"></span>
					</label>
					<div>
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show author avatars on grid cards', 'ipin' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Requires "Show Avatars" to be enabled in Settings → Discussion.', 'ipin' ); ?></p>
					</div>
				</div>

				<div class="ipin-section-title"><?php esc_html_e( 'Footer', 'ipin' ); ?></div>

				<div class="ipin-field">
					<label for="ipin_footer_text"><?php esc_html_e( 'Footer text', 'ipin' ); ?></label>
					<div>
						<input
							type="text"
							id="ipin_footer_text"
							name="ipin_footer_text"
							value="<?php echo esc_attr( ipin_get( 'ipin_footer_text', '' ) ); ?>"
							placeholder="<?php esc_attr_e( 'Optional custom footer text…', 'ipin' ); ?>"
						>
						<p class="ipin-field-desc"><?php esc_html_e( 'Leave blank to use the default site name + tagline footer.', 'ipin' ); ?></p>
					</div>
				</div>

				<?php ipin_render_save_bar(); ?>
			</div>

			<!-- ═══ TAB: APPEARANCE ═══ -->
			<div id="ipin-tab-appearance" class="ipin-tab-panel" role="tabpanel">

				<p class="ipin-info-box">
					<strong><?php esc_html_e( 'Colour Scheme', 'ipin' ); ?></strong> —
					<?php esc_html_e( 'Pick a palette. All gradients, accents, and interactive colours update automatically. Dark mode is available on all schemes.', 'ipin' ); ?>
				</p>

				<div class="ipin-section-title"><?php esc_html_e( 'Palette', 'ipin' ); ?></div>

				<div class="ipin-scheme-picker">
					<?php foreach ( $schemes as $slug => $scheme ) : ?>
					<label class="ipin-scheme-swatch <?php echo $slug === $current_scheme ? 'selected' : ''; ?>">
						<input
							type="radio"
							name="ipin_colour_scheme"
							value="<?php echo esc_attr( $slug ); ?>"
							<?php checked( $slug, $current_scheme ); ?>
						>
						<span class="swatch-grad <?php echo esc_attr( $scheme['class'] ); ?>"></span>
						<?php echo esc_html( $scheme['label'] ); ?>
					</label>
					<?php endforeach; ?>
				</div>

				<div class="ipin-section-title" style="margin-top:28px;"><?php esc_html_e( 'Dark Mode', 'ipin' ); ?></div>

				<div class="ipin-toggle-row">
					<label class="ipin-toggle">
						<input type="hidden" name="ipin_dark_mode_default" value="0">
						<input
							type="checkbox"
							name="ipin_dark_mode_default"
							value="1"
							<?php checked( 1, (int) ipin_get( 'ipin_dark_mode_default', 0 ) ); ?>
						>
						<span class="ipin-toggle-slider"></span>
					</label>
					<div>
						<span class="ipin-toggle-label"><?php esc_html_e( 'Default to dark mode', 'ipin' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Serves dark mode by default (user can still toggle). When off, follows the visitor\'s OS preference.', 'ipin' ); ?></p>
					</div>
				</div>

				<div class="ipin-section-title"><?php esc_html_e( 'Cards', 'ipin' ); ?></div>

				<div class="ipin-field">
					<label for="ipin_card_width"><?php esc_html_e( 'Card width (px)', 'ipin' ); ?></label>
					<div>
						<input
							type="number"
							id="ipin_card_width"
							name="ipin_card_width"
							value="<?php echo esc_attr( ipin_get( 'ipin_card_width', 220 ) ); ?>"
							min="140" max="400"
						>
						<p class="ipin-field-desc"><?php esc_html_e( 'Width of each pin card in pixels. 180–240px recommended for most screens.', 'ipin' ); ?></p>
					</div>
				</div>

				<div class="ipin-toggle-row">
					<label class="ipin-toggle">
						<input type="hidden" name="ipin_rounded_cards" value="0">
						<input
							type="checkbox"
							name="ipin_rounded_cards"
							value="1"
							<?php checked( 1, (int) ipin_get( 'ipin_rounded_cards', 1 ) ); ?>
						>
						<span class="ipin-toggle-slider"></span>
					</label>
					<div>
						<span class="ipin-toggle-label"><?php esc_html_e( 'Rounded card corners', 'ipin' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Applies the large border-radius to cards. Disable for a sharper, more editorial look.', 'ipin' ); ?></p>
					</div>
				</div>

				<?php ipin_render_save_bar(); ?>
			</div>

			<!-- ═══ TAB: SOCIAL ═══ -->
			<div id="ipin-tab-social" class="ipin-tab-panel" role="tabpanel">

				<p class="ipin-info-box">
					<strong><?php esc_html_e( 'Social links', 'ipin' ); ?></strong> —
					<?php esc_html_e( 'Icons appear in the top navigation bar. Leave a field blank to hide that icon.', 'ipin' ); ?>
				</p>

				<div class="ipin-section-title"><?php esc_html_e( 'Profiles', 'ipin' ); ?></div>

				<?php
				$social_fields = [
					'ipin_twitter_url'   => [ 'Twitter / X URL',  'https://x.com/yourhandle'         ],
					'ipin_facebook_url'  => [ 'Facebook URL',     'https://facebook.com/yourpage'     ],
					'ipin_instagram_url' => [ 'Instagram URL',    'https://instagram.com/yourhandle'  ],
				];

				foreach ( $social_fields as $key => [ $label, $placeholder ] ) : ?>
				<div class="ipin-field">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
					<input
						type="url"
						id="<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( ipin_get( $key, '' ) ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
					>
				</div>
				<?php endforeach; ?>

				<div class="ipin-section-title"><?php esc_html_e( 'RSS', 'ipin' ); ?></div>

				<div class="ipin-toggle-row">
					<label class="ipin-toggle">
						<input type="hidden" name="ipin_rss_visible" value="0">
						<input
							type="checkbox"
							name="ipin_rss_visible"
							value="1"
							<?php checked( 1, (int) ipin_get( 'ipin_rss_visible', 1 ) ); ?>
						>
						<span class="ipin-toggle-slider"></span>
					</label>
					<div>
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show RSS icon in navigation', 'ipin' ); ?></span>
					</div>
				</div>

				<?php ipin_render_save_bar(); ?>
			</div>

			<!-- ═══ TAB: LAYOUT ═══ -->
			<div id="ipin-tab-layout" class="ipin-tab-panel" role="tabpanel">

				<p class="ipin-info-box">
					<strong><?php esc_html_e( 'Layout options', 'ipin' ); ?></strong> —
					<?php esc_html_e( 'Control sidebar position and other structural settings for single posts and pages.', 'ipin' ); ?>
				</p>

				<div class="ipin-section-title"><?php esc_html_e( 'Single Post / Page Sidebar', 'ipin' ); ?></div>

				<div class="ipin-field">
					<label for="ipin_sidebar_position"><?php esc_html_e( 'Sidebar position', 'ipin' ); ?></label>
					<div>
						<select id="ipin_sidebar_position" name="ipin_sidebar_position">
							<?php
							$positions = [
								'right' => __( 'Right sidebar', 'ipin' ),
								'left'  => __( 'Left sidebar',  'ipin' ),
								'none'  => __( 'No sidebar (full width)', 'ipin' ),
							];
							$current = ipin_get( 'ipin_sidebar_position', 'right' );
							foreach ( $positions as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $current ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="ipin-field-desc"><?php esc_html_e( 'Applies to all single posts and standard pages. Individual pages can override via page templates.', 'ipin' ); ?></p>
					</div>
				</div>


			<?php ipin_render_save_bar(); ?>
			</div><!-- /#ipin-tab-layout -->

			<!-- TAB: ADS -->
			<div id="ipin-tab-ads" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-ads">
				<div class="ipin-info-box">
					<?php esc_html_e( 'Paste ad code (AdSense, Amazon, or any HTML) into each slot. Leave blank to disable â no empty space is added.', 'ipin' ); ?>
				</div>
				<?php foreach ( ipin_ad_slots() as $aslot => $aslot_label ) : ?>
				<div class="ipin-section-title"><?php echo esc_html( $aslot_label ); ?></div>
				<div class="ipin-field-row" style="align-items:flex-start">
					<label for="<?php echo esc_attr( $aslot ); ?>" class="sr-only"><?php echo esc_html( $aslot_label ); ?></label>
					<textarea id="<?php echo esc_attr( $aslot ); ?>" name="<?php echo esc_attr( $aslot ); ?>" rows="4"
					          style="width:100%;font-family:monospace;font-size:.82rem"
					          placeholder="<?php esc_attr_e( 'Paste ad code hereâ¦', 'ipin' ); ?>"><?php echo esc_textarea( (string) get_option( $aslot, '' ) ); ?></textarea>
				</div>
				<?php endforeach; ?>
				<?php ipin_render_save_bar(); ?>
			</div><!-- /#ipin-tab-ads -->

		</form>
	</div><!-- /#ipin-settings-wrap -->
	<?php
}


/* -------------------------------------------------------
   HELPER — save bar (reused in each tab)
   ------------------------------------------------------- */
function ipin_render_save_bar(): void {
	?>
	<div class="ipin-save-bar">
		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Save Settings', 'ipin' ); ?>
		</button>
		<span class="ipin-saved-notice">✓ <?php esc_html_e( 'Settings saved!', 'ipin' ); ?></span>
	</div>
	<?php
}
