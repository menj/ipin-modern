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
		'ipin_scheme_rotate'       => [ 'absint',                        0        ],
		'ipin_scheme_rotate_hours' => [ 'ipin_sanitize_rotate_hours',    24       ],
		'ipin_card_width'         => [ 'ipin_sanitize_card_width', 220    ],
		'ipin_card_gap'            => [ 'ipin_sanitize_card_gap',        14       ],
		'ipin_card_img_height'     => [ 'ipin_sanitize_card_img_height', 0        ],
		'ipin_card_shadow'         => [ 'ipin_sanitize_card_shadow',     'subtle' ],
		'ipin_rounded_cards'      => [ 'absint',                  1       ],
		'ipin_card_hover_zoom'     => [ 'absint',                        1        ],
		// Social
		// …plus one URL per profile in ipin_social_profiles(), added below
		'ipin_social_inline'      => [ 'ipin_sanitize_social_inline', 5   ],
		'ipin_author_sameas'      => [ 'ipin_sanitize_url_list',  ''      ],
		'ipin_fediverse_creator'  => [ 'ipin_sanitize_fediverse', ''      ],
		'ipin_rss_visible'        => [ 'absint',                  1       ],
		// Layout — homepage hero
		'ipin_hero_enabled'       => [ 'absint',                  1       ],
		'ipin_hero_bento'         => [ 'absint',                  1       ],
		'ipin_hero_title'         => [ 'sanitize_text_field',     ''      ],
		'ipin_hero_lede'          => [ 'wp_kses_post',            ''      ],
		// Layout — single posts, comments, Pinterest
		'ipin_show_post_nav'        => [ 'absint',                       1  ],
		'ipin_comment_markdown'     => [ 'absint',                       0  ],
		'ipin_pinterest_tag_id'     => [ 'ipin_sanitize_pinterest_tag',  '' ],
		'ipin_pinterest_hover_save' => [ 'absint',                       1  ],
		// Search — structured data and sameAs; see inc/seo.php
		'ipin_schema_enabled'       => [ 'absint',                       1        ],
		'ipin_schema_entity'        => [ 'ipin_sanitize_schema_entity',  'person' ],
		'ipin_schema_person'        => [ 'absint',                       0        ],
		'ipin_schema_org_name'      => [ 'sanitize_text_field',          ''       ],
		'ipin_sameas_profiles'      => [ 'absint',                       1        ],
		// Visibility — tag IDs; see inc/hidden-tags.php
		'hidden_tags'               => [ 'ipin_sanitize_hidden_tags',    [] ],
	] + array_map(
		static fn(): array => [ 'esc_url_raw', '' ],
		ipin_social_profiles()
	);
}

/**
 * Every social and identity profile the top bar can link to, in display
 * order: option key => [ icon file in assets/img/social/, name, group ].
 * The icons are the Minimalist Social Icons Pack 2.8, all 45 of them, plus
 * the Open Library mark from 4.x. Option keys from 4.x and 5.0 are kept
 * (ipin_twitter_url shows the X icon, ipin_worldcat_url OCLC's mark,
 * ipin_gamingtribe_url the GTribe mark).
 */
function ipin_social_profiles(): array {
	return [
		// Social networks
		'ipin_twitter_url'           => [ 'x',                 'X (Twitter)',            'social' ],
		'ipin_facebook_url'          => [ 'facebook',          'Facebook',               'social' ],
		'ipin_instagram_url'         => [ 'instagram',         'Instagram',              'social' ],
		'ipin_threads_url'           => [ 'threads',           'Threads',                'social' ],
		'ipin_mastodon_url'          => [ 'mastodon',          'Mastodon',               'social' ],
		'ipin_bluesky_url'           => [ 'bluesky',           'Bluesky',                'social' ],
		'ipin_linkedin_url'          => [ 'linkedin',          'LinkedIn',               'social' ],
		'ipin_pinterest_url'         => [ 'pinterest',         'Pinterest',              'social' ],
		'ipin_tiktok_url'            => [ 'tiktok',            'TikTok',                 'social' ],
		'ipin_snapchat_url'          => [ 'snapchat',          'Snapchat',               'social' ],
		'ipin_reddit_url'            => [ 'reddit',            'Reddit',                 'social' ],
		'ipin_tumblr_url'            => [ 'tumblr',            'Tumblr',                 'social' ],
		'ipin_quora_url'             => [ 'quora',             'Quora',                  'social' ],
		// Messaging and community
		'ipin_whatsapp_url'          => [ 'whatsapp',          'WhatsApp',               'messaging' ],
		'ipin_telegram_url'          => [ 'telegram',          'Telegram',               'messaging' ],
		'ipin_signal_url'            => [ 'signal',            'Signal',                 'messaging' ],
		'ipin_line_url'              => [ 'line',              'LINE',                   'messaging' ],
		'ipin_wechat_url'            => [ 'wechat',            'WeChat',                 'messaging' ],
		'ipin_discord_url'           => [ 'discord',           'Discord',                'messaging' ],
		// Video, music and creative
		'ipin_youtube_url'           => [ 'youtube',           'YouTube',                'media' ],
		'ipin_vimeo_url'             => [ 'vimeo',             'Vimeo',                  'media' ],
		'ipin_twitch_url'            => [ 'twitch',            'Twitch',                 'media' ],
		'ipin_spotify_url'           => [ 'spotify',           'Spotify',                'media' ],
		'ipin_soundcloud_url'        => [ 'soundcloud',        'SoundCloud',             'media' ],
		'ipin_suno_url'              => [ 'suno',              'Suno',                   'media' ],
		'ipin_flickr_url'            => [ 'flickr',            'Flickr',                 'media' ],
		'ipin_behance_url'           => [ 'behance',           'Behance',                'media' ],
		'ipin_dribbble_url'          => [ 'dribbble',          'Dribbble',               'media' ],
		// Writing and publishing
		'ipin_medium_url'            => [ 'medium',            'Medium',                 'writing' ],
		'ipin_substack_url'          => [ 'substack',          'Substack',               'writing' ],
		'ipin_wordpress_url'         => [ 'wordpress',         'WordPress',              'writing' ],
		'ipin_wordpress_profile_url' => [ 'wordpress-profile', 'WordPress.org profile',  'writing' ],
		'ipin_goodreads_url'         => [ 'goodreads',         'Goodreads',              'writing' ],
		'ipin_issuu_url'             => [ 'issuu',             'Issuu',                  'writing' ],
		'ipin_scribd_url'            => [ 'scribd',            'Scribd',                 'writing' ],
		'ipin_wikipedia_url'         => [ 'wikipedia',         'Wikipedia',              'writing' ],
		'ipin_wikidata_url'          => [ 'wikidata',          'Wikidata',               'writing' ],
		'ipin_academia_url'          => [ 'academia',          'Academia.edu',           'writing' ],
		// Identifiers and libraries
		'ipin_orcid_url'             => [ 'orcid',             'ORCID',                  'identity' ],
		'ipin_isni_url'              => [ 'isni',              'ISNI',                   'identity' ],
		'ipin_viaf_url'              => [ 'viaf',              'VIAF',                   'identity' ],
		'ipin_worldcat_url'          => [ 'oclc',              'WorldCat',               'identity' ],
		'ipin_openlibrary_url'       => [ 'openlibrary',       'Open Library',           'identity' ],
		// Work, code and games
		'ipin_github_url'            => [ 'github',            'GitHub',                 'work' ],
		'ipin_fiverr_url'            => [ 'fiverr',            'Fiverr',                 'work' ],
		'ipin_gamingtribe_url'       => [ 'gtribe',            'GamingTribe',            'work' ],
	];
}

/** Settings-page cards for the profile groups, slug => title. */
function ipin_social_groups(): array {
	return [
		'social'    => __( 'Social Networks', 'ipin-modern' ),
		'messaging' => __( 'Messaging & Community', 'ipin-modern' ),
		'media'     => __( 'Video, Music & Creative', 'ipin-modern' ),
		'writing'   => __( 'Writing & Publishing', 'ipin-modern' ),
		'identity'  => __( 'Identifiers & Libraries', 'ipin-modern' ),
		'work'      => __( 'Work, Code & Games', 'ipin-modern' ),
	];
}

/** Top-bar icons shown before the rest fold into the "More" menu. */
function ipin_sanitize_social_inline( mixed $value ): int {
	return min( count( ipin_social_profiles() ), absint( $value ) );
}

/** The colour schemes tokens.css defines, slug => label. */
function ipin_colour_schemes(): array {
	return [
		'vivid'  => __( 'Vivid',  'ipin-modern' ),
		'ocean'  => __( 'Ocean',  'ipin-modern' ),
		'ember'  => __( 'Ember',  'ipin-modern' ),
		'forest' => __( 'Forest', 'ipin-modern' ),
		'mono'   => __( 'Mono',   'ipin-modern' ),
		// Restored from 4.5 (rebuilt in OKLCH in 5.1)
		'rosegold' => __( 'Rose Gold', 'ipin-modern' ),
		'aurora'   => __( 'Aurora',    'ipin-modern' ),
		'dusk'     => __( 'Dusk',      'ipin-modern' ),
		'copper'   => __( 'Copper',    'ipin-modern' ),
		'arctic'   => __( 'Arctic',    'ipin-modern' ),
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

function ipin_sanitize_card_gap( mixed $value ): int {
	return max( 4, min( 48, absint( $value ) ) );
}

/** 0 keeps each image's natural height. */
function ipin_sanitize_card_img_height( mixed $value ): int {
	return min( 600, absint( $value ) );
}

/** Card shadow depths, slug => label. */
function ipin_card_shadows(): array {
	return [
		'none'       => __( 'None', 'ipin-modern' ),
		'subtle'     => __( 'Subtle (default)', 'ipin-modern' ),
		'pronounced' => __( 'Pronounced', 'ipin-modern' ),
	];
}

function ipin_sanitize_card_shadow( mixed $value ): string {
	$value = sanitize_key( (string) $value );
	return array_key_exists( $value, ipin_card_shadows() ) ? $value : 'subtle';
}

/** Auto-rotate periods in hours => label. */
function ipin_rotate_periods(): array {
	return [
		1   => __( '1 hour', 'ipin-modern' ),
		6   => __( '6 hours', 'ipin-modern' ),
		12  => __( '12 hours', 'ipin-modern' ),
		24  => __( '24 hours (recommended)', 'ipin-modern' ),
		48  => __( '2 days', 'ipin-modern' ),
		72  => __( '3 days', 'ipin-modern' ),
		168 => __( '1 week', 'ipin-modern' ),
	];
}

function ipin_sanitize_rotate_hours( mixed $value ): int {
	$value = absint( $value );
	return array_key_exists( $value, ipin_rotate_periods() ) ? $value : 24;
}

/** Pinterest Tag IDs are numeric. */
function ipin_sanitize_pinterest_tag( mixed $value ): string {
	return preg_replace( '/[^0-9]/', '', (string) $value ) ?? '';
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
		'search'     => [ 'icon' => '&#x1f50d;',        'label' => __( 'Search',     'ipin-modern' ) ],
		'visibility' => [ 'icon' => '&#x1f441;',        'label' => __( 'Visibility', 'ipin-modern' ) ],
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
				<p class="ipin-card__title"><?php esc_html_e( 'Auto-Rotate Scheme', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Show each visitor a different colour scheme. The pick is kept in their browser and changes on a timer, so there is no server load and no cookie.', 'ipin-modern' ); ?></p>

				<?php ipin_render_toggle(
					'ipin_scheme_rotate', 0,
					__( 'Enable auto-rotate', 'ipin-modern' ),
					__( 'The Colour Scheme above stays the fallback for visitors without JavaScript or browser storage.', 'ipin-modern' )
				); ?>

				<div class="ipin-field">
					<label for="ipin_scheme_rotate_hours"><?php esc_html_e( 'Rotate every', 'ipin-modern' ); ?></label>
					<div>
						<select id="ipin_scheme_rotate_hours" name="ipin_scheme_rotate_hours">
							<?php $cur_hours = (int) ipin_get( 'ipin_scheme_rotate_hours', 24 );
							foreach ( ipin_rotate_periods() as $hours => $lbl ) : ?>
							<option value="<?php echo esc_attr( (string) $hours ); ?>"<?php selected( $cur_hours, $hours ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="ipin-field-desc"><?php esc_html_e( 'How long a scheme lasts before the next visit picks a new one.', 'ipin-modern' ); ?></p>
					</div>
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

				<div class="ipin-field">
					<label for="ipin_card_gap"><?php esc_html_e( 'Card gap', 'ipin-modern' ); ?></label>
					<div>
						<div class="ipin-slider-wrap">
							<input type="range" id="ipin_card_gap" name="ipin_card_gap"
								min="4" max="48" value="<?php echo esc_attr( ipin_get( 'ipin_card_gap', 14 ) ); ?>">
							<span class="ipin-slider-value" id="ipin_card_gap_val"
								aria-live="polite"><?php echo esc_html( ipin_get( 'ipin_card_gap', 14 ) ); ?>px</span>
						</div>
						<p class="ipin-field-desc"><?php esc_html_e( 'Space between cards. Phones use 10 px regardless.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_card_img_height"><?php esc_html_e( 'Image height', 'ipin-modern' ); ?></label>
					<div>
						<?php $img_h = (int) ipin_get( 'ipin_card_img_height', 0 ); ?>
						<div class="ipin-slider-wrap">
							<input type="range" id="ipin_card_img_height" name="ipin_card_img_height"
								min="0" max="600" step="10" value="<?php echo esc_attr( (string) $img_h ); ?>"
								data-zero-label="<?php esc_attr_e( 'Natural', 'ipin-modern' ); ?>">
							<span class="ipin-slider-value" id="ipin_card_img_height_val"
								aria-live="polite"><?php echo $img_h ? esc_html( $img_h . 'px' ) : esc_html__( 'Natural', 'ipin-modern' ); ?></span>
						</div>
						<p class="ipin-field-desc"><?php esc_html_e( 'Crop every card image to one height for an even grid. 0 keeps each image at its own proportions, the masonry look.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_card_shadow"><?php esc_html_e( 'Card shadow', 'ipin-modern' ); ?></label>
					<div>
						<select id="ipin_card_shadow" name="ipin_card_shadow">
							<?php $shadow_val = ipin_get( 'ipin_card_shadow', 'subtle' );
							foreach ( ipin_card_shadows() as $slug => $lbl ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $shadow_val, $slug ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
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

				<?php ipin_render_toggle(
					'ipin_card_hover_zoom', 1,
					__( 'Zoom image on hover', 'ipin-modern' ),
					__( 'The card image scales up slightly on hover and keyboard focus.', 'ipin-modern' )
				); ?>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-appearance -->


		<!-- ====================================================
		     TAB: SOCIAL
		     ==================================================== -->
		<div id="ipin-tab-social" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-social">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Top Bar', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Every profile with an address below gets an icon in the top bar, in the order listed on this page. After the first few, the rest open from a "More profiles" button so the bar stays tidy. Leave an address blank to hide that icon.', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_social_inline"><?php esc_html_e( 'Icons before "More"', 'ipin-modern' ); ?></label>
					<div>
						<input type="number" id="ipin_social_inline" name="ipin_social_inline"
							value="<?php echo esc_attr( ipin_get( 'ipin_social_inline', 5 ) ); ?>"
							min="0" max="<?php echo esc_attr( (string) count( ipin_social_profiles() ) ); ?>">
						<p class="ipin-field-desc"><?php esc_html_e( 'How many icons show directly in the bar. 0 puts every profile in the menu.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<?php
			$ipin_profiles = ipin_social_profiles();
			foreach ( ipin_social_groups() as $group => $group_title ) : ?>
			<div class="ipin-card">
				<p class="ipin-card__title"><?php echo esc_html( $group_title ); ?></p>
				<?php foreach ( $ipin_profiles as $key => [ $icon, $name, $in_group ] ) :
					if ( $in_group !== $group ) continue; ?>
				<div class="ipin-field">
					<label for="<?php echo esc_attr( $key ); ?>">
						<span class="ipin-social-label-icon" aria-hidden="true"><?php echo ipin_social_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput -- bundled SVG file ?></span>
						<?php echo esc_html( $name ); ?>
					</label>
					<input type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( ipin_get( $key, '' ) ); ?>"
						placeholder="https://">
				</div>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Mastodon', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'The profiles above also feed the site\'s sameAs links; see the Search tab.', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_fediverse_creator"><?php esc_html_e( 'Mastodon / fediverse handle', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_fediverse_creator" name="ipin_fediverse_creator"
							value="<?php echo esc_attr( ipin_get( 'ipin_fediverse_creator', '' ) ); ?>"
							placeholder="@you@mastodon.social" autocomplete="off" spellcheck="false">
						<p class="ipin-field-desc"><?php esc_html_e( 'Credits you on Mastodon link previews (fediverse:creator), adds a rel="me" link so Mastodon can verify this site on your profile, and joins your sameAs links. A profile URL works too.', 'ipin-modern' ); ?></p>
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

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Posts & Comments', 'ipin-modern' ); ?></p>

				<?php ipin_render_toggle(
					'ipin_show_post_nav', 1,
					__( 'Show previous / next links on single posts', 'ipin-modern' ),
					__( 'Posts with a hidden tag are skipped.', 'ipin-modern' )
				); ?>

				<?php ipin_render_toggle(
					'ipin_comment_markdown', 0,
					__( 'Markdown in comments', 'ipin-modern' ),
					__( 'Commenters can use **bold**, *italic*, ~~strikethrough~~, `code` and [links](https://example.com). Block elements and images are not rendered. Markdown for posts is switched on per post, in the editor sidebar.', 'ipin-modern' )
				); ?>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Pinterest', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Single posts always carry Rich Pin meta. The Tag ID adds Pinterest conversion tracking; find it in your Pinterest Ads account under Conversions. It is the one thing this theme can load from another server, and only when you set it.', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<label for="ipin_pinterest_tag_id"><?php esc_html_e( 'Pinterest Tag ID', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_pinterest_tag_id" name="ipin_pinterest_tag_id"
							value="<?php echo esc_attr( ipin_get( 'ipin_pinterest_tag_id', '' ) ); ?>"
							placeholder="2612345678901" inputmode="numeric" autocomplete="off">
						<p class="ipin-field-desc"><?php esc_html_e( 'Leave blank for no tracking.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<?php ipin_render_toggle(
					'ipin_pinterest_hover_save', 1,
					__( 'Show the Pinterest Save button in the lightbox', 'ipin-modern' ),
					''
				); ?>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-layout -->


		<!-- ====================================================
		     TAB: SEARCH
		     ==================================================== -->
		<div id="ipin-tab-search" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-search">

			<?php $ipin_seo_plugin = ipin_seo_plugin_name(); if ( '' !== $ipin_seo_plugin ) : ?>
			<div class="ipin-card ipin-card--notice" role="note">
				<p class="ipin-card__desc"><?php printf(
					/* translators: %s = SEO plugin name */
					esc_html__( '%s is active, so the theme leaves description tags, Open Graph tags and structured data to it. The settings below apply again if it is switched off.', 'ipin-modern' ),
					'<strong>' . esc_html( $ipin_seo_plugin ) . '</strong>'
				); ?></p>
			</div>
			<?php endif; ?>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Structured Data', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'JSON-LD in each page\'s head, following Google Search Central\'s guidelines: WebSite on the home page, Article and BreadcrumbList on posts and articles, VideoObject on video pins with a featured image, and ProfilePage on author pages. Check a page with Google\'s Rich Results Test after changing anything here.', 'ipin-modern' ); ?></p>

				<?php ipin_render_toggle(
					'ipin_schema_enabled', 1,
					__( 'Output structured data', 'ipin-modern' ),
					__( 'Switch off to print no JSON-LD at all. Description and Open Graph tags are unaffected.', 'ipin-modern' )
				); ?>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Site Identity', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Who the site belongs to. Search engines use this, with the sameAs links below, to connect the site to one knowledge-panel entity. Every page points to it as publisher.', 'ipin-modern' ); ?></p>

				<div class="ipin-field">
					<span class="ipin-field-label" id="ipin_schema_entity_label"><?php esc_html_e( 'This site represents', 'ipin-modern' ); ?></span>
					<div role="radiogroup" aria-labelledby="ipin_schema_entity_label">
						<?php $ipin_entity = ipin_sanitize_schema_entity( ipin_get( 'ipin_schema_entity', 'person' ) );
						foreach ( ipin_schema_entities() as $slug => $lbl ) : ?>
						<label class="ipin-radio">
							<input type="radio" name="ipin_schema_entity" value="<?php echo esc_attr( $slug ); ?>"<?php checked( $ipin_entity, $slug ); ?>>
							<?php echo esc_html( $lbl ); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_schema_person"><?php esc_html_e( 'Person', 'ipin-modern' ); ?></label>
					<div>
						<select id="ipin_schema_person" name="ipin_schema_person">
							<option value="0"><?php esc_html_e( 'First administrator (default)', 'ipin-modern' ); ?></option>
							<?php $ipin_person = (int) ipin_get( 'ipin_schema_person', 0 );
							foreach ( get_users( [ 'capability' => 'edit_posts', 'orderby' => 'display_name' ] ) as $u ) : ?>
							<option value="<?php echo esc_attr( (string) $u->ID ); ?>"<?php selected( $ipin_person, (int) $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="ipin-field-desc"><?php esc_html_e( 'Used when the site represents a person. Their display name, biographical info, profile picture and Website field (Users → Profile) fill in the Person entity, and their posts credit that entity as author.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<div class="ipin-field">
					<label for="ipin_schema_org_name"><?php esc_html_e( 'Organization name', 'ipin-modern' ); ?></label>
					<div>
						<input type="text" id="ipin_schema_org_name" name="ipin_schema_org_name"
							value="<?php echo esc_attr( ipin_get( 'ipin_schema_org_name', '' ) ); ?>"
							placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
						<p class="ipin-field-desc"><?php esc_html_e( 'Used when the site represents an organization. Leave blank for the site title. The logo is the Custom Logo, or else the Site Icon (Appearance → Customize → Site Identity).', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'sameAs Links', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Other pages that belong to the same person or organization: profiles, other sites, authority records. They go on the site identity only; other authors get just their own Website field.', 'ipin-modern' ); ?></p>

				<?php ipin_render_toggle(
					'ipin_sameas_profiles', 1,
					__( 'Include the profiles from the Social tab', 'ipin-modern' ),
					__( 'Every profile address filled in there, plus the Mastodon handle.', 'ipin-modern' )
				); ?>

				<div class="ipin-field">
					<label for="ipin_author_sameas"><?php esc_html_e( 'More sameAs URLs', 'ipin-modern' ); ?></label>
					<div>
						<textarea id="ipin_author_sameas" name="ipin_author_sameas" rows="4"
							placeholder="https://example.com&#10;https://www.wikidata.org/wiki/Q00000"><?php echo esc_textarea( ipin_get( 'ipin_author_sameas', '' ) ); ?></textarea>
						<p class="ipin-field-desc"><?php esc_html_e( 'One URL per line, for pages with no top-bar icon: other sites you run, a Wikidata item, a library authority record.', 'ipin-modern' ); ?></p>
					</div>
				</div>

				<?php $ipin_same_as = ipin_same_as_urls(); ?>
				<div class="ipin-field">
					<span class="ipin-field-label"><?php esc_html_e( 'Output now', 'ipin-modern' ); ?></span>
					<div>
						<?php if ( $ipin_same_as ) : ?>
						<ol class="ipin-sameas-preview">
							<?php foreach ( $ipin_same_as as $url ) : ?>
							<li><?php echo esc_html( $url ); ?></li>
							<?php endforeach; ?>
						</ol>
						<?php else : ?>
						<p class="ipin-field-desc"><?php esc_html_e( 'No sameAs links yet.', 'ipin-modern' ); ?></p>
						<?php endif; ?>
						<p class="ipin-field-desc"><?php esc_html_e( 'As saved; save to refresh. When the site is a person, their Website field is added first.', 'ipin-modern' ); ?></p>
					</div>
				</div>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-search -->


		<!-- ====================================================
		     TAB: VISIBILITY
		     ==================================================== -->
		<div id="ipin-tab-visibility" class="ipin-tab-panel" role="tabpanel" aria-labelledby="ipin-tab-btn-visibility">

			<div class="ipin-card">
				<p class="ipin-card__title"><?php esc_html_e( 'Hidden Tags', 'ipin-modern' ); ?></p>
				<p class="ipin-card__desc"><?php esc_html_e( 'Posts carrying any tag ticked here drop out of the grid, archives, search, feeds, the sitemap and previous/next links, and the tag itself disappears from tag lists and clouds. Each post stays reachable at its own address, and the tag\'s own archive still lists them (marked noindex).', 'ipin-modern' ); ?></p>

				<?php
				$hidden_ids = array_map( 'intval', (array) get_option( 'hidden_tags', [] ) );
				$all_tags   = get_terms( [ 'taxonomy' => 'post_tag', 'hide_empty' => false, 'orderby' => 'name' ] );
				?>
				<?php if ( is_wp_error( $all_tags ) || ! $all_tags ) : ?>
					<p class="ipin-field-desc"><?php esc_html_e( 'This site has no tags yet.', 'ipin-modern' ); ?></p>
				<?php else : ?>
				<div class="ipin-tag-picker">
					<label for="ipin-tag-filter" class="screen-reader-text"><?php esc_html_e( 'Filter tags', 'ipin-modern' ); ?></label>
					<input type="search" id="ipin-tag-filter" class="ipin-tag-filter" autocomplete="off"
						placeholder="<?php esc_attr_e( 'Filter tags…', 'ipin-modern' ); ?>" aria-controls="ipin-tag-list">
					<?php // Always sent, so unticking every box still saves an empty list (0 is dropped by the sanitiser). ?>
					<input type="hidden" name="hidden_tags[]" value="0">
					<ul id="ipin-tag-list" class="ipin-tag-list" role="list">
						<?php foreach ( $all_tags as $tag ) : ?>
						<li data-name="<?php echo esc_attr( mb_strtolower( $tag->name ) ); ?>">
							<label>
								<input type="checkbox" name="hidden_tags[]" value="<?php echo esc_attr( (string) $tag->term_id ); ?>"
									<?php checked( in_array( (int) $tag->term_id, $hidden_ids, true ) ); ?>>
								<?php echo esc_html( $tag->name ); ?>
								<span class="ipin-tag-count"><?php echo esc_html( number_format_i18n( (int) $tag->count ) ); ?></span>
							</label>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
			</div>

			<?php ipin_render_save_bar(); ?>
		</div><!-- /#ipin-tab-visibility -->

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


/* -------------------------------------------------------
   HELPER — one switch row (same markup as the rows above)
   ------------------------------------------------------- */
function ipin_render_toggle( string $key, int $default, string $label, string $desc ): void {
	?>
	<div class="ipin-toggle-row">
		<div class="ipin-toggle-cell">
			<label class="ipin-switch" for="<?php echo esc_attr( $key ); ?>"
				aria-label="<?php echo esc_attr( $label ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="0">
				<input type="checkbox" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="1"
					<?php checked( 1, (int) ipin_get( $key, $default ) ); ?>>
				<span class="ipin-switch__track"></span>
				<span class="ipin-switch__thumb"></span>
			</label>
		</div>
		<div class="ipin-toggle-body">
			<span class="ipin-toggle-label"><?php echo esc_html( $label ); ?></span>
			<?php if ( '' !== $desc ) : ?>
			<p class="ipin-toggle-desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
