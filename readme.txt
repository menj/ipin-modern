=== iPin Modern ===
Contributors: menj
Tags: masonry, pinterest, grid, dark-mode, photography, portfolio, responsive, infinite-scroll, lightbox, custom-colors, custom-logo, threaded-comments, translation-ready, accessibility-ready
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 4.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A bold, colorful Pinterest-style masonry theme. PHP 8.x, WCAG 2.2 AA, dark mode, 5 colour schemes, lightbox, infinite scroll, Google Site Kit compatible, zero external dependencies.

== Description ==

iPin Modern is a complete ground-up rewrite of the original iPin Pinterest-clone theme. It preserves the masonry grid aesthetic while replacing every line of legacy code with modern, secure, accessible PHP and CSS.

**Key Features**

* Masonry grid with infinite scroll and frontpage lightbox
* Popular posts sort bar — Latest, This week, This month, All time (ranked by comment count, no extra DB tables)
* 5 colour schemes: Vivid, Ocean, Ember, Forest, Mono
* Flash-free dark mode with localStorage persistence and OS preference detection
* Social share buttons on every post and inside the lightbox: Pinterest, X (Twitter), Facebook, Copy Link
* Ad slots: header banner, 5 grid positions, above/below featured photo on single posts
* Sideblog via the `ipin_article` custom post type (/article/ and /articles/)
* Popular Posts sidebar widget with configurable period and count
* Fully tabbed admin settings page with AJAX save (no page reload)
* Google Site Kit and AdSense compatible — Auto Ads work out of the box; manual ad unit code (including `<script>` tags) is preserved correctly
* WCAG 2.2 AA compliant: skip link, focus trap in lightbox, aria-modal, 4.5:1 contrast on all text, 44×44px touch targets, prefers-reduced-motion, forced-colors (Windows HCM)
* Bootstrap-free — native CSS Flex and Grid only
* Zero external JavaScript dependencies — all three grid libraries (masonry, imagesloaded, infinite scroll) are purpose-built and bundled
* PHP 8.0+ with strict types throughout

**Bundled JavaScript Libraries (all custom, iPin-authored)**

* `jquery.imagesloaded.min.js` v1.0.0 — proxy Image pattern, jQuery Deferred support
* `jquery.masonry.min.js` v1.0.0 — shortest-column bin-packing, incremental appended(), debounced resize
* `jquery.infinitescroll.min.js` v1.0.0 — scroll-proximity trigger, AJAX page fetch, next-href advancement

**Google Site Kit / AdSense**

For Auto Ads: install the Google Site Kit plugin, connect AdSense, and enable Auto Ads. The theme's `wp_head()` and `wp_body_open()` hooks are in the correct positions — no theme changes needed.

For manual ad units: paste the full AdSense code block (the `<ins>` element and the `<script>` push line) into any slot in Appearance → iPin Settings → Ads. Script tags are preserved exactly as pasted.

== Installation ==

1. Download the `ipin-modern-4.1.4.zip` file.
2. In your WordPress admin, go to Appearance → Themes → Add New → Upload Theme.
3. Choose the zip file and click Install Now.
4. Click Activate.
5. Go to Settings → Permalinks and click Save Changes to register the Sideblog rewrite rules.
6. Go to Appearance → iPin Settings to configure colour scheme, dark mode, ads, and layout.
7. Optionally assign a menu to Top Navigation via Appearance → Menus.
8. Optionally add widgets to Right Sidebar or Left Sidebar via Appearance → Widgets.

**Manual installation (FTP)**

1. Extract `ipin-modern-4.1.4.zip`.
2. Upload the `ipin-modern/` folder to `/wp-content/themes/`.
3. Activate the theme in Appearance → Themes.
4. Follow steps 5–8 above.

== Frequently Asked Questions ==

= Do I need to install any plugins? =
No. The theme is self-contained — all JavaScript is bundled and no plugins are required for any core feature.

= Is it compatible with Google Site Kit and AdSense? =
Yes. Auto Ads work immediately after connecting Site Kit — the theme has `wp_head()` and `wp_body_open()` in the correct positions. For manual ad units, paste the full `<ins>` + `<script>` code from AdSense into Appearance → iPin Settings → Ads. Script tags are preserved.

= How do I add a new colour scheme? =
Add a `[data-scheme="myscheme"] { }` block to `assets/css/tokens.css`, add a `[data-theme="dark"][data-scheme="myscheme"] { }` dark-mode override, then add an entry to the `$schemes` array in `inc/admin-options.php`.

= How do I use the Popular Posts sort bar? =
It appears automatically on the homepage. Click Latest, This week, This month, or All time. Sorting is based on WordPress's native comment count — no additional database tables are created. You can also add the iPin Popular Posts widget to any sidebar via Appearance → Widgets.

= How do I set up the Sideblog? =
After activation, a Sideblog menu item appears in the WordPress admin. Create articles there — they are available at `/article/{slug}/` individually and at `/articles/` as an archive.

= What does the lightbox load? =
Pin data (image, title, description, author, share links, and latest comments) is fetched via AJAX when a card is clicked. Nothing is pre-loaded in hidden markup — there is no impact on page load time.

= Can I use a child theme? =
Yes. Create a standard WordPress child theme. Override any template by placing a copy in your child theme directory. Override CSS tokens without editing source files by adding a `:root { }` block to your child theme stylesheet.

= Does it work with the block editor (Gutenberg)? =
Yes. `editor-style.css` is registered so block editor typography and colours match the frontend. Full Site Editing (FSE) template parts are on the roadmap for a future release.

= What PHP version is required? =
PHP 8.0 or later. The theme uses strict types, union types, match expressions, and arrow functions throughout.

= Is it translation-ready? =
Yes. All user-facing strings use `__()` / `esc_html_e()` with the `ipin` text domain. Generate a `.pot` file with: `wp i18n make-pot . languages/ipin.pot --domain=ipin`

== Changelog ==

= 4.1.4 =
* Fixed: Duplicate admin asset enqueue — ipin_admin_scripts() in admin-options.php was a stale copy of ipin_enqueue_admin_assets() in enqueue.php; both were hooked to admin_enqueue_scripts, double-registering ipin-admin-css and ipin-admin-js and calling wp_localize_script twice. Removed the stale copy from admin-options.php.

= 4.1.3 =
* Fixed: Lightbox was completely non-functional — the article element was missing data-post-id, so the JS selector never matched any cards and no post IDs were collected. Adding data-post-id to the article tag restores the lightbox.
* Fixed: All front-end and admin CSS/JS assets were versioned at a hardcoded "3.0", preventing cache-busting on theme updates. Version string now reads from the theme header dynamically.
* Fixed: add_editor_style() was called inside wp_enqueue_scripts (frontend only) so block editor styles were never applied. Moved to after_setup_theme where it belongs.
* Fixed: role="menu" on dropdown <ul> in nav-walker — the ARIA menu role implies application-menu keyboard semantics that this nav does not implement, causing incorrect AT announcements. Removed.
* Fixed: custom-logo height hint was 50px but display CSS was updated to 52px in 4.1.2. Hint updated to match.
* Fixed: "This week" label on the sort bar did not match "Last 7 days" label in the Popular Posts widget — both now read "Last 7 days".

= 4.1.2 =
* Changed: Navigation bar height increased from 64 px to 96 px for a more airy, prominent feel.
* Changed: Nav inner horizontal padding increased from 24 px to 40 px per side.
* Changed: Gap between all nav bar elements increased from 12 px to 28 px.
* Changed: Logo text size increased from 1.4 rem to 1.75 rem; font-weight remains 800.
* Changed: Custom logo image height increased from 40 px to 52 px.
* Changed: Nav link padding, font-size, and font-weight increased for bolder appearance.
* Changed: Search bar input width increased from 130 px to 180 px; button padding enlarged.
* Changed: Social icon and dark-mode toggle button size increased from 44 px to 48 px.
* Changed: Nav bottom border increased from 2 px to 3 px; shadow upgraded to shadow-md.
* Token: --nav-height updated from 64px to 96px in tokens.css (body padding-top auto-adjusts).

= 4.1.1 =
* Fixed: Nested <a> elements when custom logo active (WCAG 4.1.1 Parsing). Custom logo now wrapped in <span> instead of <a>.
* Fixed: aria-hidden="true" on scheme card inner span made radio buttons nameless (WCAG 4.1.2). Removed aria-hidden.
* Fixed: Lightbox close/prev/next buttons were outside the focus trap, allowing Tab to escape the dialog (WCAG 2.1.2). Buttons now appended inside the overlay element.
* Fixed: hideLlighting() typo in lightbox.js — loading spinner never cleared on AJAX error. Corrected to hideLoading().
* Fixed: Copy-link confirmation "Copied!" not announced to screen readers (WCAG 4.1.3). Added aria-live="polite" region in footer.php.

= 4.1.0 =
* Redesign: Admin settings UI rewritten to modern_settings_ui design system spec v1.0.0.
* Changed: Root wrapper class is now .plugin-settings-root — all CSS scoped exclusively within it. No :root overrides. No global host changes.
* Changed: Tokens renamed to --plugin-accent / --plugin-surface / --plugin-border / --plugin-focus, inheriting from WP admin theme colour by default.
* Changed: All boolean options now use .ipin-switch toggle components (track + animated thumb) instead of plain checkboxes.
* Changed: Colour scheme picker converted to a grid of visual selector cards with gradient swatch previews.
* Changed: Card width control is now a range slider with a live px value display (aria-live).
* Changed: Tab keyboard navigation implements ARIA roving tabindex (ArrowLeft / ArrowRight).
* Changed: Version badge reads live from theme headers. Footer added: "Developed by MENJ" + GitHub link.
* Changed: All JS scoped to wrapper element — no global namespace pollution.

= 4.0.2 =
* Added: Global manual ads on/off switch in the Ads tab — disable all manual slots in one click to hand placement to Google Site Kit Auto Ads.
* Added: Per-slot enable/disable checkbox on each of the 8 ad slots with live Active/Paused badge.
* Added: ipin_manual_ads_on() and ipin_ad_slot_enabled() helpers; render functions check both before outputting HTML.

= 4.0.1 =
* Fixed: Ad slot code was never saved — AJAX save handler was missing all eight ad slot fields.
* Fixed: `wp_kses_post` was stripping `<script>` tags from AdSense code. Replaced with `ipin_sanitize_ad_code()` which preserves raw ad HTML for manage_options users.
* Added: Google Site Kit / AdSense usage instructions in the Ads tab info box.
* Fixed: Garbled UTF-8 encoding in Ads tab placeholder text.

= 4.0.0 =
* Added: Popular posts sort bar (Latest / This week / This month / All time).
* Added: iPin_Popular_Posts_Widget for sidebars.
* Added: Frontpage lightbox with keyboard navigation, focus trap, AJAX data load, and share buttons.
* Added: Social share buttons on single posts and inside the lightbox (Pinterest, X, Facebook, Copy Link).
* Added: Ad slot system with 8 configurable positions and admin UI.
* Added: ipin_article custom post type (Sideblog).
* Added: inc/popular-posts.php, inc/ads.php, inc/post-types.php.
* Added: lightbox.css, updated masonry.css and single.css.
* Changed: All three grid JS libraries (masonry, imagesloaded, infinitescroll) replaced with in-house implementations.
* Changed: lightbox.js updated to use ipinData instead of removed ipinSocial bridge.
* Removed: All social-network features (likes, repins, follows, upload, OAuth, boards, notifications).
* Bumped: style.css Version 3.0 → 4.0.

= 3.0.0 =
* Added: /assets/ directory structure (css/, js/, img/, fonts/).
* Added: inc/ directory — all PHP logic extracted from functions.php into dedicated files.
* Added: inc/template-tags.php, inc/nav-walker.php, inc/enqueue.php, inc/admin-options.php, inc/customizer.php.
* Changed: All asset paths updated from css/ and js/ to assets/css/ and assets/js/.
* Changed: functions.php reduced to a pure bootstrap file.

= 2.0.0 =
* Added: Modular CSS — tokens.css, base.css, nav.css, masonry.css, single.css, admin.css, editor-style.css.
* Added: 5 colour schemes via [data-scheme] CSS attribute selectors.
* Added: Dark mode via [data-theme="dark"] with localStorage persistence and flash-free init.
* Added: Tabbed admin settings page with AJAX save (Appearance → iPin Settings).
* Added: ipin.admin.js — tab switching, swatch picker, AJAX save.
* Added: ipin_dynamic_css_and_scheme() — inline custom properties + synchronous scheme init script.

= 1.0.0 =
* Security: Removed malware — remote HTTP call to backlinks.com injected into header.php.
* Rewrite: Complete rewrite for PHP 8.x compatibility (strict types, typed parameters, match, arrow functions).
* Removed: Bootstrap 2/3 (bootstrap.min.css, bootstrap.min.js).
* Added: CSS custom properties replacing all hardcoded colour values.
* Added: Dark mode with localStorage persistence.
* Added: Font Awesome 6 replacing Font Awesome 4.
* Added: Accessible hamburger navigation.
* Added: Scroll-to-top button.
* Added: ipin.custom.js.

== Upgrade Notice ==

= 4.1.3 =
Critical fix: the lightbox was broken since launch — data-post-id was missing from card elements. Also fixes asset cache-busting, block editor styles, and a bad ARIA role on nav dropdowns. Upgrade strongly recommended.

= 4.1.2 =
Navigation bar height changed from 64 px to 96 px via the --nav-height CSS token. Child themes or custom CSS that uses var(--nav-height) for vertical positioning (e.g. sticky sidebars, hero offset) should be checked and adjusted.

= 4.1.1 =
WCAG 2.2 AA audit: fixes nested anchor bug with custom logos, lightbox focus trap escape, and screen reader announcements. Recommended for all installs.

= 4.1.0 =
Admin UI rewritten to design system spec. Toggle switches replace checkboxes; colour schemes use visual selector cards; all styles scoped to wrapper with no host theme changes. Drop-in upgrade — no settings migration needed.

= 4.0.2 =
Adds global and per-slot on/off toggles for manual ad units. Makes switching between manual AdSense units and Google Site Kit Auto Ads a one-click operation.

= 4.0.1 =
Fixes two bugs: ad slot code was silently discarded on save, and wp_kses_post was stripping AdSense script tags. Upgrade recommended for all users using the Ads feature.

= 4.0.0 =
Major release. Adds lightbox, popular posts sort bar, share buttons, ad slots, and Sideblog CPT. Removes all social-network features. All three grid JS libraries replaced with in-house implementations — no external downloads required. See UPGRADING.md for migration steps.

= 3.0.0 =
Directory restructure — assets moved to /assets/css/ and /assets/js/, logic split into /inc/ files. Child themes referencing old asset paths must update them. See UPGRADING.md.
