<?php
/**
 * iPin Modern — inc/markdown.php
 *
 * Markdown authoring support for pin posts and sideblog articles.
 *
 * ── How it works ────────────────────────────────────────────────────────
 *
 * Markdown is opt-in per post via the "_ipin_markdown" post meta flag. When
 * enabled, the raw Markdown stored in post_content is parsed to HTML at render
 * time and the result is cached in "_ipin_markdown_html". The cache is keyed
 * by an md5 hash of the raw content ("_ipin_markdown_hash") and is invalidated
 * automatically whenever the post is saved.
 *
 * ── Parser ──────────────────────────────────────────────────────────────
 *
 * cebe/markdown 1.2 (MIT), GitHub flavour, vendored in inc/vendor/cebe-markdown/
 * and extended by Ipin_Markdown (inc/class-ipin-markdown.php): headings,
 * paragraphs, emphasis, strikethrough, links and bare URLs, images, quotes,
 * ordered/unordered/task lists, fenced and indented code, tables, rules and
 * inline HTML. The output is filtered with wp_kses_post() before caching.
 * The library loads only when a page renders Markdown.
 *
 * ── Supported post types ────────────────────────────────────────────────
 *
 *   post          — pin posts (single.php)
 *   ipin_article  — sideblog articles (single.php renders both)
 *
 * ── Comment Markdown ────────────────────────────────────────────────────
 *
 * When "Markdown in comments" is on (ipin_comment_markdown, Settings →
 * Layout), commenters may use inline Markdown: bold, italic, strikethrough,
 * inline code, and links. Block-level elements (headings, tables, lists) and
 * images are not rendered in comments — plain paragraphs only. The result is
 * filtered to the comment tag allow-list and its links get rel="ugc".
 *
 * Restored in 5.1 from 4.5.
 *
 * ── Developer hooks ─────────────────────────────────────────────────────
 *
 *   Filter 'ipin_markdown_enabled' ( bool $enabled, int $post_id )
 *     Override whether a specific post renders as Markdown.
 *
 *   Filter 'ipin_markdown_parsed' ( string $html, string $raw, int $post_id )
 *     Modify the parsed HTML before it is cached and returned.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;


/* ═══════════════════════════════════════════════════════
   CONSTANTS
   ═══════════════════════════════════════════════════════ */

define( 'IPIN_MARKDOWN_META',      '_ipin_markdown' );       // bool: opt-in flag
define( 'IPIN_MARKDOWN_HTML_META', '_ipin_markdown_html' );  // string: cached HTML
define( 'IPIN_MARKDOWN_HASH_META', '_ipin_markdown_hash' );  // string: content md5


/* ═══════════════════════════════════════════════════════
   PARSER
   cebe\markdown is loaded on first use: the autoloader and
   the Ipin_Markdown class are registered here, once.
   ═══════════════════════════════════════════════════════ */

/**
 * Autoloader for the vendored cebe\markdown classes. If a plugin has
 * already loaded cebe/markdown, PHP finds its classes first and this
 * loader is never asked.
 */
function ipin_markdown_autoload( string $class ): void {
	$prefix = 'cebe\\markdown\\';
	if ( ! str_starts_with( $class, $prefix ) ) {
		return;
	}
	$file = __DIR__ . '/vendor/cebe-markdown/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_readable( $file ) ) {
		require $file;
	}
}

/**
 * The shared parser. $comments = true gives the comment flavour, where a
 * single newline is a line break, as people expect in a comment box.
 */
function ipin_markdown_parser( bool $comments = false ): \Ipin_Markdown {
	static $parsers = [];

	if ( ! class_exists( 'Ipin_Markdown', false ) ) {
		spl_autoload_register( 'ipin_markdown_autoload' );
		require_once __DIR__ . '/class-ipin-markdown.php';
	}

	$key = $comments ? 'comments' : 'posts';
	if ( ! isset( $parsers[ $key ] ) ) {
		$parser                 = new \Ipin_Markdown();
		$parser->html5          = true;
		$parser->enableNewlines = $comments;
		$parsers[ $key ]        = $parser;
	}
	return $parsers[ $key ];
}


/* ═══════════════════════════════════════════════════════
   PUBLIC HELPERS
   ═══════════════════════════════════════════════════════ */

/**
 * Is Markdown enabled for a given post?
 */
function ipin_markdown_enabled( int $post_id ): bool {
	$enabled = (bool) get_post_meta( $post_id, IPIN_MARKDOWN_META, true );
	return (bool) apply_filters( 'ipin_markdown_enabled', $enabled, $post_id );
}

/**
 * Parse a Markdown string to sanitised HTML.
 * Uses Ipin_Markdown (cebe/markdown, GitHub flavour).
 */
function ipin_parse_markdown( string $markdown ): string {
	// Sanitise output — preserves all block and inline HTML elements that
	// make sense in post content while stripping anything potentially harmful.
	return wp_kses_post( ipin_markdown_parser()->parse( $markdown ) );
}

/**
 * Return clean plain text from a post's raw DB content.
 *
 * This is the canonical function for schema.php and opengraph.php to use
 * when they need a plain-text description or word count from post content.
 * It handles both Markdown posts and classic (HTML) posts correctly.
 *
 * For Markdown posts:
 *   1. Parse Markdown → HTML (same pass the_content filter uses).
 *   2. Strip all HTML tags → plain text prose.
 *   3. Trim to $words words.
 *
 * For classic (non-Markdown) posts:
 *   1. Strip HTML tags from raw post_content.
 *   2. Trim to $words words.
 *
 * Why not just call get_the_content() or apply_filters('the_content')?
 *   - get_the_content() returns unfiltered raw DB content.
 *   - apply_filters('the_content', ...) is expensive: it runs Gutenberg
 *     block rendering, shortcode expansion, wpautop, and every plugin that
 *     hooks the_content — all to get text we then immediately strip.
 *   This helper parses only what is needed, reading raw DB fields directly.
 *
 * @param string $raw      Raw text from get_post_field('post_content') or
 *                         get_post_field('post_excerpt').
 * @param int    $post_id  Post ID — used to check Markdown flag.
 * @param int    $words    Maximum word count (0 = no limit).
 * @return string Clean plain text, suitable for og:description / schema.
 */
function ipin_markdown_to_plain_text( string $raw, int $post_id, int $words = 0 ): string {
	if ( $raw === '' ) return '';

	if ( ipin_markdown_enabled( $post_id ) ) {
		// Parse Markdown → HTML → strip all tags
		$text = wp_strip_all_tags( ipin_parse_markdown( $raw ) );
	} else {
		// Classic content — strip any HTML tags already present
		$text = wp_strip_all_tags( $raw );
	}

	// Collapse whitespace
	$text = preg_replace( '/\s+/', ' ', $text );
	$text = trim( $text );

	if ( $words > 0 ) {
		$text = wp_trim_words( $text, $words, '' );
	}

	return $text;
}


/* ═══════════════════════════════════════════════════════
   CONTENT FILTER
   ═══════════════════════════════════════════════════════ */

/**
 * Replace the raw Markdown stored in post_content with parsed HTML.
 * Hooked onto 'the_content' at priority 1 (before wpautop at 10).
 *
 * WHY is_singular() is NOT used here:
 *   The WordPress REST API calls the_content filter (for block editor previews,
 *   headless consumers, and the /wp/v2/posts endpoint) but is_singular() returns
 *   false because the REST API never initialises the main WP query. A guard on
 *   is_singular() would silently skip our parser on REST requests, leaving raw
 *   Markdown in the response. get_the_ID() > 0 is the correct check — it reads
 *   the global $post object, which the REST API DOES populate.
 *
 * WHY wpautop is removed here (not in a wp action):
 *   The 'wp' action fires only on classic template requests, not REST API calls.
 *   WordPress allows a lower-priority callback (us at 1) to remove a higher-
 *   priority callback (wpautop at 10) from within the same running filter chain —
 *   WP re-reads the callback list between priority groups. Removing here is safe,
 *   reliable, and works in every context including REST.
 */
function ipin_markdown_the_content( string $content ): string {
	$post_id = (int) get_the_ID();
	if ( ! $post_id || ! ipin_markdown_enabled( $post_id ) ) return $content;

	// Remove wpautop NOW — we're at priority 1, wpautop is at 10.
	// WP processes priority groups sequentially, so removing a group-10 callback
	// from within group-1 takes effect before group-10 runs.
	remove_filter( 'the_content', 'wpautop' );
	// Also remove shortcode_unautop which would otherwise re-add <p> tags around
	// shortcodes after wpautop was removed. Markdown authors may include shortcodes
	// in their posts; we want them rendered without extra paragraph wrapping.
	remove_filter( 'the_content', 'shortcode_unautop' );

	$html = ipin_markdown_get_cached( $post_id, $content );

	return (string) apply_filters( 'ipin_markdown_parsed', $html, $content, $post_id );
}
add_filter( 'the_content', 'ipin_markdown_the_content', 1 );

// The separate 'wp' action for wpautop removal is no longer needed —
// removal now happens inside ipin_markdown_the_content (see above).
// Removing both ensures wpautop is gone in ALL contexts: template, REST, headless.

/**
 * Sanitise excerpts — strip Markdown syntax so the_excerpt() returns
 * clean text for OG tags and schema descriptions.
 */
function ipin_markdown_the_excerpt( string $excerpt ): string {
	// get_the_ID() reads the global post — valid inside any WP loop or
	// on a singular page. No is_singular() guard: Markdown excerpts may
	// be requested from widgets, shortcodes, or archive loops as well.
	$post_id = (int) get_the_ID();
	if ( ! $post_id || ! ipin_markdown_enabled( $post_id ) ) return $excerpt;

	// Delegate to the shared plain-text helper — parses Markdown and strips
	// all HTML tags so the excerpt is clean prose, suitable for og:description
	// and schema descriptions.
	return ipin_markdown_to_plain_text( $excerpt !== '' ? $excerpt : (string) get_post_field( 'post_content', $post_id ), $post_id, 55 );
}
add_filter( 'get_the_excerpt', 'ipin_markdown_the_excerpt', 1 );


/* ═══════════════════════════════════════════════════════
   CACHE
   ═══════════════════════════════════════════════════════ */

/**
 * Return cached HTML for a Markdown post, or parse and cache it.
 *
 * Cache storage: two post meta keys per post.
 *   _ipin_markdown_html  — the rendered HTML string
 *   _ipin_markdown_hash  — md5 of the raw Markdown, used to detect staleness
 *
 * Invalidation: ipin_markdown_flush_cache() is hooked on save_post so the
 * cache is always fresh after an edit. The hash check is a belt-and-suspenders
 * safety net for any code that bypasses save_post.
 */
function ipin_markdown_get_cached( int $post_id, string $raw ): string {
	// The parser and renderer revision are part of the key, so HTML cached
	// by an earlier parser (4.x's built-in one) or an earlier Ipin_Markdown
	// is re-rendered once, on first view. Bump "ipin-N" when output changes.
	$hash   = md5( 'cebe-markdown-1.2|ipin-2|' . $raw );
	$stored = (string) get_post_meta( $post_id, IPIN_MARKDOWN_HASH_META, true );
	$cached = (string) get_post_meta( $post_id, IPIN_MARKDOWN_HTML_META, true );

	if ( $cached !== '' && $stored === $hash ) {
		return $cached;
	}

	$html = ipin_parse_markdown( $raw );

	update_post_meta( $post_id, IPIN_MARKDOWN_HTML_META, $html );
	update_post_meta( $post_id, IPIN_MARKDOWN_HASH_META, $hash );

	return $html;
}

/**
 * Flush the Markdown HTML cache when a post is saved.
 */
function ipin_markdown_flush_cache( int $post_id ): void {
	if ( wp_is_post_revision( $post_id ) ) return;
	delete_post_meta( $post_id, IPIN_MARKDOWN_HTML_META );
	delete_post_meta( $post_id, IPIN_MARKDOWN_HASH_META );
}
add_action( 'save_post', 'ipin_markdown_flush_cache' );


/* ═══════════════════════════════════════════════════════
   COMMENT MARKDOWN
   Inline-only — bold, italic, strikethrough, code, links.
   No headings, tables, or block-level elements in comments.
   ═══════════════════════════════════════════════════════ */

function ipin_markdown_comment_text( string $text ): string {
	if ( ! (bool) get_option( 'ipin_comment_markdown', 0 ) ) return $text;

	// Inline syntax only, paragraph by paragraph: parseParagraph() ignores
	// block elements, and the comment flavour turns single newlines into <br>.
	$out = [];
	foreach ( preg_split( '/\n\s*\n/', str_replace( [ "\r\n", "\r" ], "\n", trim( $text ) ) ) ?: [] as $p ) {
		if ( '' !== trim( $p ) ) {
			$out[] = '<p>' . ipin_markdown_parser( true )->parseParagraph( $p ) . '</p>';
		}
	}

	// Comments are visitor content: keep only the tags a comment may carry
	// (images, for one, are not allowed), and mark every link user-generated,
	// as core does for comment links.
	global $allowedtags;
	$allowed = array_merge( (array) $allowedtags, [ 'p' => [], 'br' => [] ] );
	$html    = wp_kses( implode( "\n", $out ), $allowed );
	return (string) preg_replace_callback(
		'|<a (.+?)>|i',
		static fn( array $m ): string => wp_rel_callback( $m, 'nofollow ugc' ),
		$html
	);
}

/**
 * Plain text of a comment for short previews (grid cards, lightbox):
 * Markdown syntax removed when comment Markdown is on, so previews show
 * "bold" rather than "**bold**".
 */
function ipin_comment_plain_text( string $raw ): string {
	if ( (bool) get_option( 'ipin_comment_markdown', 0 ) ) {
		$raw = ipin_markdown_comment_text( $raw );
	}
	return trim( wp_strip_all_tags( $raw ) );
}

// Priority 8: before core's make_clickable (9), which would otherwise turn the
// URL inside [text](url) into a link of its own and break the Markdown link.
add_filter( 'comment_text', 'ipin_markdown_comment_text', 8 );


/* ═══════════════════════════════════════════════════════
   ASSET LOADING
   Enqueue markdown.css on singular pages where Markdown is on.
   ═══════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! is_singular() ) return;
	$post_id = (int) get_the_ID();
	if ( ! ipin_markdown_enabled( $post_id ) ) return;

	wp_enqueue_style(
		'ipin-markdown',
		get_template_directory_uri() . '/assets/css/markdown.css',
		[ 'ipin-single' ],
		wp_get_theme()->get( 'Version' )
	);
}, 20 );


/* ═══════════════════════════════════════════════════════
   BLOCK EDITOR NOTICE
   When Markdown mode is on, the block editor cannot render
   raw Markdown as blocks. Show a banner in the block editor
   pointing the author to switch to the classic editor.
   ═══════════════════════════════════════════════════════ */

add_action( 'enqueue_block_editor_assets', static function (): void {
	$screen = get_current_screen();
	if ( ! $screen ) return;
	if ( ! in_array( $screen->post_type, [ 'post', 'ipin_article' ], true ) ) return;

	$post_id = (int) ( $_GET['post'] ?? 0 );
	if ( ! $post_id || ! ipin_markdown_enabled( $post_id ) ) return;

	// Inline notice script — no separate JS file needed
	wp_add_inline_script(
		'wp-blocks',
		'wp.domReady(function(){
			wp.data.dispatch("core/notices").createWarningNotice(
				"' . esc_js( __( 'Markdown mode is enabled for this post. Use the Classic Editor to write Markdown — the block editor stores block grammar, which is incompatible with Markdown source.', 'ipin-modern' ) ) . '",
				{ id: "ipin-markdown-notice", isDismissible: true }
			);
		});'
	);
} );


/* ═══════════════════════════════════════════════════════
   META BOX — opt-in toggle for post and ipin_article
   Extends the existing ipin_pin_details meta box (post)
   and adds a standalone box for ipin_article.
   ═══════════════════════════════════════════════════════ */

add_action( 'add_meta_boxes', static function (): void {
	foreach ( [ 'post', 'ipin_article' ] as $type ) {
		add_meta_box(
			'ipin_markdown_box',
			__( 'Markdown', 'ipin-modern' ),
			'ipin_markdown_meta_box_render',
			$type,
			'side',
			'default'
		);
	}
} );

function ipin_markdown_meta_box_render( WP_Post $post ): void {
	$enabled = ipin_markdown_enabled( $post->ID );
	$parser  = 'cebe/markdown 1.2 (GitHub flavour)';
	wp_nonce_field( 'ipin_save_markdown_' . $post->ID, 'ipin_markdown_nonce' );
	?>
	<style>
		#ipin_markdown_box .ipin-md-row { margin: 0 0 10px; }
		#ipin_markdown_box .ipin-md-toggle { display: flex; align-items: center; gap: 8px; }
		#ipin_markdown_box .ipin-md-toggle label { font-weight: 600; margin: 0; cursor: pointer; }
		#ipin_markdown_box .ipin-md-parser { font-size: .78em; color: #646970; margin: 6px 0 0; }
		#ipin_markdown_box .ipin-md-cheatsheet { font-size: .78em; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 8px; display: none; }
		#ipin_markdown_box .ipin-md-cheatsheet.is-visible { display: block; }
		#ipin_markdown_box .ipin-md-cheatsheet code { background: #f0f0f1; padding: 1px 4px; border-radius: 3px; font-size: .9em; }
		#ipin_markdown_box .ipin-md-cheatsheet table { width: 100%; border-collapse: collapse; }
		#ipin_markdown_box .ipin-md-cheatsheet td { padding: 2px 4px; vertical-align: top; }
	</style>

	<div class="ipin-md-row">
		<div class="ipin-md-toggle">
			<input type="checkbox"
			       id="ipin_markdown_enabled"
			       name="ipin_markdown_enabled"
			       value="1"
			       <?php checked( $enabled ); ?>>
			<label for="ipin_markdown_enabled">
				<?php esc_html_e( 'Write in Markdown', 'ipin-modern' ); ?>
			</label>
		</div>
		<p class="ipin-md-parser">
			<?php
			/* translators: %s: parser name */
			printf( esc_html__( 'Parser: %s', 'ipin-modern' ), '<strong>' . esc_html( $parser ) . '</strong>' );
			?>
		</p>
	</div>

	<div class="ipin-md-cheatsheet<?php echo $enabled ? ' is-visible' : ''; ?>" id="ipin-md-cheat">
		<table>
			<tr><td><code># H1</code></td><td><?php esc_html_e( 'Heading 1', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>## H2</code></td><td><?php esc_html_e( 'Heading 2', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>**bold**</code></td><td><?php esc_html_e( 'Bold', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>*italic*</code></td><td><?php esc_html_e( 'Italic', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>~~strike~~</code></td><td><?php esc_html_e( 'Strikethrough', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>`code`</code></td><td><?php esc_html_e( 'Inline code', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>```lang</code></td><td><?php esc_html_e( 'Code block', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>> quote</code></td><td><?php esc_html_e( 'Blockquote', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>- item</code></td><td><?php esc_html_e( 'List item', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>- [ ] task</code></td><td><?php esc_html_e( 'Task item', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>[text](url)</code></td><td><?php esc_html_e( 'Link', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>![alt](url)</code></td><td><?php esc_html_e( 'Image', 'ipin-modern' ); ?></td></tr>
			<tr><td><code>---</code></td><td><?php esc_html_e( 'Horizontal rule', 'ipin-modern' ); ?></td></tr>
		</table>
	</div>

	<script>
	(function(){
		var cb    = document.getElementById('ipin_markdown_enabled');
		var cheat = document.getElementById('ipin-md-cheat');
		if ( ! cb || ! cheat ) return;
		cb.addEventListener('change', function(){
			cheat.classList.toggle('is-visible', cb.checked);
		});
	}());
	</script>
	<?php
}


/* ═══════════════════════════════════════════════════════
   META BOX SAVE
   ═══════════════════════════════════════════════════════ */

add_action( 'save_post', static function ( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! in_array( get_post_type( $post_id ), [ 'post', 'ipin_article' ], true ) ) return;

	$nonce = sanitize_text_field( wp_unslash( $_POST['ipin_markdown_nonce'] ?? '' ) );
	if ( ! wp_verify_nonce( $nonce, 'ipin_save_markdown_' . $post_id ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$enabled = ! empty( $_POST['ipin_markdown_enabled'] );
	if ( $enabled ) {
		update_post_meta( $post_id, IPIN_MARKDOWN_META, '1' );
	} else {
		delete_post_meta( $post_id, IPIN_MARKDOWN_META );
	}
} );
