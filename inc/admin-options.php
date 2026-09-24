<?php
/**
 * iPin Modern — Tabbed Admin Options Page
 * Registers the settings, renders the page, handles AJAX save.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------
   SETTINGS SCHEMA
   The single place a setting is declared: key => [ sanitizer,
   default ]. Both save paths below (AJAX and the no-JS
   options.php post) sanitise through these callbacks, because
   register_setting() attaches each one to its option's
   sanitize_option_{$key} filter, which update_option() runs.
   ------------------------------------------------------- */
function ipin_settings_schema(): array {
	return [
		// General
		'ipin_frontpage_comments' => [ 'absint',                  3       ],
		'ipin_posts_per_page'     => [ 'ipin_sanitize_per_page',  12      ],
		'ipin_show_avatars_grid'  => [ 'absint',                  1       ],
		'ipin_footer_text'        => [ 'wp_kses_post',            ''      ],
		// Appearance
		'ipin_colour_scheme'      => [ 'ipin_sanitize_scheme',    'vivid' ],
		'ipin_dark_mode_default'  => [ 'absint',                  0       ],
		'ipin_card_width'         => [ 'ipin_sanitize_card_width', 220    ],
		'ipin_rounded_cards'      => [ 'absint',                  1       ],
		// Social
		'ipin_twitter_url'        => [ 'esc_url_raw',             ''      ],
		'ipin_facebook_url'       => [ 'esc_url_raw',             ''      ],
		'ipin_instagram_url'      => [ 'esc_url_raw',             ''      ],
		'ipin_author_sameas'      => [ 'ipin_sanitize_url_list',  ''      ],
		'ipin_fediverse_creator'  => [ 'ipin_sanitize_fediverse', ''      ],
		'ipin_rss_visible'        => [ 'absint',                  1       ],
		// Layout — homepage hero
		'ipin_hero_enabled'       => [ 'absint',                  1       ],
		'ipin_hero_bento'         => [ 'absint',                  1       ],
		'ipin_hero_title'         => [ 'sanitize_text_field',     ''      ],
		'ipin_hero_lede'          => [ 'wp_kses_post',            ''      ],
	];
}

/** The colour schemes tokens.css defines, slug => label. */
function ipin_colour_schemes(): array {
	return [
		'vivid'  => __( 'Vivid',  'ipin-modern' ),
		'ocean'  => __( 'Ocean',  'ipin-modern' ),
		'ember'  => __( 'Ember',  'ipin-modern' ),
		'forest' => __( 'Forest', 'ipin-modern' ),
		'mono'   => __( 'Mono',   'ipin-modern' ),
	];
}

function ipin_sanitize_scheme( mixed $value ): string {
	$value = sanitize_key( (string) $value );
	return array_key_exists( $value, ipin_colour_schemes() ) ? $value : 'vivid';
}

function ipin_sanitize_per_page( mixed $value ): int {
	return max( 1, min( 100, absint( $value ) ) );
}

function ipin_sanitize_card_width( mixed $value ): int {
	return max( 140, min( 400, absint( $value ) ) );
}


/* -------------------------------------------------------
   REGISTER SETTINGS
   ------------------------------------------------------- */
function ipin_register_settings(): void {
	foreach ( ipin_settings_schema() as $key => [ $sanitize, $default ] ) {
		register_setting( 'ipin_options_group', $key, [
			'sanitize_callback' => $sanitize,
			'default'           => $default,
		] );
	}
}
add_action( 'admin_init', 'ipin_register_settings' );


/* -------------------------------------------------------
   AJAX SAVE HANDLER
   Progressive enhancement over the options.php form post.
   Sanitising happens inside update_option() via the
   callbacks registered above — nothing is duplicated here.
   ------------------------------------------------------- */
function ipin_ajax_save_options(): void {
	check_ajax_referer( 'ipin_save_options', 'ipin_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'You do not have permission to change these settings.', 'ipin-modern' ) ], 403 );
	}

	foreach ( array_keys( ipin_settings_schema() ) as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above
		if ( array_key_exists( $key, $_POST ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised by the registered callback
			update_option( $key, wp_unslash( $_POST[ $key ] ) );
		}
	}

	wp_send_json_success( [ 'message' => __( 'Settings saved.', 'ipin-modern' ) ] );
}
add_action( 'wp_ajax_ipin_save_options', 'ipin_ajax_save_options' );


/* -------------------------------------------------------
   ADMIN MENU
   ------------------------------------------------------- */
function ipin_admin_menu(): void {
	add_theme_page(
		__( 'iPin Settings', 'ipin-modern' ),
		__( 'iPin Settings', 'ipin-modern' ),
		'manage_options',
		'ipin-settings',
		'ipin_render_settings_page'
	);
}
add_action( 'admin_menu', 'ipin_admin_menu' );


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
		'general'    => [ 'icon' => '&#x2699;&#xfe0f;', 'label' => __( 'General',    'ipin-modern' ) ],
		'appearance' => [ 'icon' => '&#x1f3a8;',        'label' => __( 'Appearance', 'ipin-modern' ) ],
		'social'     => [ 'icon' => '&#x1f517;',        'label' => __( 'Social',     'ipin-modern' ) ],
		'layout'     => [ 'icon' => '&#x1f4d0;',        'label' => __( 'Layout',     'ipin-modern' ) ],
	];

	$scheme  = ipin_get( 'ipin_colour_scheme', 'vivid' );
	$version = wp_get_theme()->get( 'Version' );
	?>
<div class="plugin-settings-root">

	<!-- Header -->
	<div class="ipin-header">
		<span class="ipin-header__icon" aria-hidden="true">&#x1f4cc;</span>
		<div class="ipin-header__body">
			<h1 class="ipin-header__title"><?php esc_html_e( 'iPin Modern', 'ipin-modern' ); ?></h1>
			<p class="ipin-header__desc"><?php esc_html_e( 'Pinterest-style masonry theme settings', 'ipin-modern' ); ?></p>
		</div>
		<span class="ipin-header__badge">v<?php echo esc_html( $version ); ?></span>
	</div>

	<!-- Tab navigation -->
	<ul class="ipin-tabs-nav" role="tablist">
		<?php foreach ( $tabs as $slug => $tab ) : ?>
		<li role="presentation">
			<button
				data-tab="<?php echo esc_attr( $slug ); ?>"
				id="ipin-tab-btn-<?php echo esc_attr( $slug ); ?>"
				role="tab"
				aria-selected="<?php echo $slug === 'general' ? 'true' : 'false'; ?>"
				aria-controls="ipin-tab-<?php echo esc_attr( $slug ); ?>"
				tabindex="<?php echo $slug === 'general' ? '0' : '-1'; ?>"
				class="<?php echo $slug === 'general' ? 'ipin-active' : ''; ?>"
			>
				<span class="ipin-tab-icon" aria-hidden="true"><?php echo $tab['icon']; ?></span>
				<?php echo esc_html( $tab['label'] ); ?>
			</button>
		</li>
		<?php endforeach; ?>
	</ul>

	<?php settings_errors(); // "Settings saved." after a no-JS options.php save ?>

	<form id="ipin-settings-form" method="post" action="options.php">
		<?php settings_fields( 'ipin_options_group' ); ?>

		<!-- ====================================================
		     TAB: GENERAL
		     ==================================================== -->
		<div id="ipin-tab-general" class="ipin-tab-panel ipin-active" role="tabpanel" aria-labelledby="ipin-tab-btn-general">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Homepage Grid', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Control how posts appear on your grid homepage.', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_frontpage_comments"><?php esc_html_e( 'Comments per card', 'ipin-modern' ); ?></label>
					<div>
						<input type="number" id="ipin_frontpage_comments" name="ipin_frontpage_comments"
							value="<?php echo esc_attr( ipin_get( 'ipin_frontpage_comments', 3 ) ); ?>" min="0" max="10">
						<p class="ipin-field-desc"><?php esc_html_e( 'Number of recent comments per card. Set to 0 to hide.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_posts_per_page"><?php esc_html_e( 'Posts per page', 'ipin-modern' ); ?></label>
					<div>
						<input type="number" id="ipin_posts_per_page" name="ipin_posts_per_page"
							value="<?php echo esc_attr( ipin_get( 'ipin_posts_per_page', 12 ) ); ?>" min="1" max="100">
						<p class="ipin-field-desc"><?php esc_html_e( 'Pins per batch on the homepage, archives and search; infinite scroll loads the next batch. Overrides "Blog pages show at most" in Settings > Reading.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Display', 'ipin-modern' ); ?></p>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_show_avatars_grid"
							aria-label="<?php esc_html_e( 'Show author avatars on grid cards', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_show_avatars_grid" value="0">
							<input type="checkbox" id="ipin_show_avatars_grid" name="ipin_show_avatars_grid" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_show_avatars_grid', 1 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show author avatars on grid cards', 'ipin-modern' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Requires "Show Avatars" enabled in Settings > Discussion.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Footer', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_footer_text"><?php esc_html_e( 'Footer text', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_footer_text" name="ipin_footer_text"
							value="<?php echo esc_attr( ipin_get( 'ipin_footer_text', '' ) ); ?>"
							placeholder="<?php esc_attr_e( 'Optional custom footer text...', 'ipin-modern' ); ?>">
						<p class="ipin-field-desc"><?php esc_html_e( 'Leave blank to use the default site name + tagline.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-general -->


		<!-- ====================================================
		     TAB: APPEARANCE
		     ==================================================== -->
		<div id="ipin-tab-appearance" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-appearance">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Colour Scheme', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Pick a palette. All accents and interactive colours update automatically. Dark mode is available on all schemes.', 'ipin-modern' ); ?></p>

				<div class="ipin-scheme-grid">
					<?php foreach ( ipin_colour_schemes() as $slug => $label ) : ?>
					<label class="ipin-scheme-card">
						<input type="radio" name="ipin_colour_scheme" value="<?php echo esc_attr( $slug ); ?>"
							<?php checked( $slug, $scheme ); ?>>
						<span class="ipin-scheme-card__inner">
							<?php // data-scheme pulls this scheme's real --grad-brand from tokens.css ?>
							<span class="ipin-swatch" data-scheme="<?php echo esc_attr( $slug ); ?>"></span>
							<?php echo esc_html( $label ); ?>
						</span>
					</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Dark Mode', 'ipin-modern' ); ?></p>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_dark_mode_default"
							aria-label="<?php esc_html_e( 'Default to dark mode', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_dark_mode_default" value="0">
							<input type="checkbox" id="ipin_dark_mode_default" name="ipin_dark_mode_default" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_dark_mode_default', 0 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Default to dark mode', 'ipin-modern' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Serves dark mode by default. Visitor can still toggle. When off, follows OS preference.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Cards', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_card_width"><?php esc_html_e( 'Card width', 'ipin-modern' ); ?></label>
					<div>
						<div class="ipin-slider-wrap">
							<input type="range" id="ipin_card_width" name="ipin_card_width"
								min="140" max="400" value="<?php echo esc_attr( ipin_get( 'ipin_card_width', 220 ) ); ?>">
							<span class="ipin-slider-value" id="ipin_card_width_val"
								aria-live="polite"><?php echo esc_html( ipin_get( 'ipin_card_width', 220 ) ); ?>px</span>
						</div>
						<p class="ipin-field-desc"><?php esc_html_e( 'Width of each pin card. 180-240 px recommended.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_rounded_cards"
							aria-label="<?php esc_html_e( 'Rounded card corners', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_rounded_cards" value="0">
							<input type="checkbox" id="ipin_rounded_cards" name="ipin_rounded_cards" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_rounded_cards', 1 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Rounded card corners', 'ipin-modern' ); ?></span>
						<p class="ipin-toggle-desc"><?php esc_html_e( 'Disable for a sharper, more editorial look.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-appearance -->


		<!-- ====================================================
		     TAB: SOCIAL
		     ==================================================== -->
		<div id="ipin-tab-social" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-social">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Social Profiles', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Icons appear in the top navigation bar. Leave blank to hide that icon.', 'ipin-modern' ); ?></p>

				<?php
				$social = [
					'ipin_twitter_url'   => [ 'Twitter / X', 'https://x.com/yourhandle'        ],
					'ipin_facebook_url'  => [ 'Facebook',    'https://facebook.com/yourpage'    ],
					'ipin_instagram_url' => [ 'Instagram',   'https://instagram.com/yourhandle' ],
				];
				foreach ( $social as $key => [ $lbl, $ph ] ) : ?>
				<div class="ipin-field">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></label>
					<input type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( ipin_get( $key, '' ) ); ?>"
						placeholder="<?php echo esc_attr( $ph ); ?>">
				</div>
				<?php endforeach; ?>

				<div class="ipin-field">
					<label for="ipin_fediverse_creator"><?php esc_html_e( 'Mastodon / fediverse handle', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_fediverse_creator" name="ipin_fediverse_creator"
							value="<?php echo esc_attr( ipin_get( 'ipin_fediverse_creator', '' ) ); ?>"
							placeholder="@you@mastodon.social" autocomplete="off" spellcheck="false">
						<p class="ipin-field-desc"><?php esc_html_e( 'Credits you on Mastodon link previews (fediverse:creator), adds a rel="me" link so Mastodon can verify this site on your profile, and joins your sameAs links. A profile URL works too.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_author_sameas"><?php esc_html_e( 'Also-me profile URLs (schema.org sameAs)', 'ipin-modern' ); ?></label>
					<div>
						<textarea id="ipin_author_sameas" name="ipin_author_sameas" rows="4"
							placeholder="https://menj.bio&#10;https://menj.blog"><?php echo esc_textarea( ipin_get( 'ipin_author_sameas', '' ) ); ?></textarea>
						<p class="ipin-field-desc"><?php esc_html_e( 'One URL per line. Added to the author Person schema (together with the profiles above) so search engines link this site to your other properties.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'RSS', 'ipin-modern' ); ?></p>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_rss_visible"
							aria-label="<?php esc_html_e( 'Show RSS icon in navigation', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_rss_visible" value="0">
							<input type="checkbox" id="ipin_rss_visible" name="ipin_rss_visible" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_rss_visible', 1 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show RSS icon in navigation', 'ipin-modern' ); ?></span>
					</div>
				</div>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-social -->


		<!-- ====================================================
		     TAB: LAYOUT
		     ==================================================== -->
		<div id="ipin-tab-layout" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-layout">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Homepage Hero', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Statement heading and lede paragraph shown above the grid on the first page of the homepage.', 'ipin-modern' ); ?></p>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_hero_enabled"
							aria-label="<?php esc_html_e( 'Show homepage hero', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_hero_enabled" value="0">
							<input type="checkbox" id="ipin_hero_enabled" name="ipin_hero_enabled" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_hero_enabled', 1 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show homepage hero', 'ipin-modern' ); ?></span>
					</div>
				</div>

				<div class="ipin-toggle-row">
					<div class="ipin-toggle-cell">
						<label class="ipin-switch" for="ipin_hero_bento"
							aria-label="<?php esc_html_e( 'Show featured-pin bento panel', 'ipin-modern' ); ?>">
							<input type="hidden" name="ipin_hero_bento" value="0">
							<input type="checkbox" id="ipin_hero_bento" name="ipin_hero_bento" value="1"
								<?php checked( 1, (int) ipin_get( 'ipin_hero_bento', 1 ) ); ?>>
							<span class="ipin-switch__track"></span>
							<span class="ipin-switch__thumb"></span>
						</label>
					</div>
					<div class="ipin-toggle-body">
						<span class="ipin-toggle-label"><?php esc_html_e( 'Show featured-pin bento panel', 'ipin-modern' ); ?></span>
						<span class="ipin-toggle-desc"><?php esc_html_e( 'Featured pin (first sticky post, or the newest pin) plus board stats beside the hero text.', 'ipin-modern' ); ?></span>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_hero_title"><?php esc_html_e( 'Hero heading', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_hero_title" name="ipin_hero_title"
							value="<?php echo esc_attr( ipin_get( 'ipin_hero_title', '' ) ); ?>"
							placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
						<p class="ipin-field-desc"><?php esc_html_e( 'Leave blank to use the site title. Wrap one word in *asterisks* to give it the gradient accent.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_hero_lede"><?php esc_html_e( 'Lede paragraph', 'ipin-modern' ); ?></label>
					<div>
						<textarea id="ipin_hero_lede" name="ipin_hero_lede" rows="3"
							placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( ipin_get( 'ipin_hero_lede', '' ) ); ?></textarea>
						<p class="ipin-field-desc"><?php esc_html_e( 'Leave blank to use the site tagline. Basic HTML (links, emphasis) is allowed.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-layout -->

	</form>

	<!-- Footer -->
	<div class="ipin-footer">
		<span><?php esc_html_e( 'Developed by', 'ipin-modern' ); ?> <a href="https://github.com/menj" target="_blank" rel="noopener noreferrer">MENJ</a></span>
		<span><a href="https://github.com/menj" target="_blank" rel="noopener noreferrer">GitHub</a></span>
	</div>

</div><!-- /.plugin-settings-root -->
	<?php
}


/* -------------------------------------------------------
   HELPER — save bar (reused at bottom of each tab)
   ------------------------------------------------------- */
function ipin_render_save_bar(): void {
	?>
	<div class="ipin-save-bar">
		<button type="submit" class="ipin-btn-primary"><?php esc_html_e( 'Save Settings', 'ipin-modern' ); ?></button>
		<span class="ipin-saved-notice" aria-live="polite">&#x2713; <?php esc_html_e( 'Settings saved!', 'ipin-modern' ); ?></span>
		<span class="ipin-error-notice" role="alert" hidden></span>
	</div>
	<?php
}
