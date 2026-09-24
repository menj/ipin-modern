=== iPin Modern ===
Contributors: menj
Tags: masonry, grid-layout, photography, portfolio, dark-mode
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Pinterest-style masonry theme for images and video. Five colour schemes with dark mode, WCAG 2.2 AA, no jQuery, and no requests to other servers.

== Description ==

iPin Modern lays your posts out as a masonry board of pins. It began as a rewrite of Genkisan's original iPin, and version 5.0 replaces its whole front end.

What you get:

* A homepage hero with your heading and a short lede, plus an optional panel with a featured pin, board stats and category links. Set it under Appearance → iPin Settings → Layout.
* A masonry grid in plain JavaScript. It lays out as soon as the page loads, reflows when the window resizes, and loads the next batch as you scroll. With JavaScript off it falls back to CSS columns and ordinary pagination.
* A full-screen lightbox. Click a pin to see the whole image, or play the video, with the description, share links and latest comments. Arrow keys move between pins and Escape closes it.
* Video pins from a direct file link (.mp4, .webm) or a YouTube or Vimeo link.
* 5 colour schemes (Vivid, Ocean, Ember, Forest and Mono), each with a dark mode. Every text and control colour is checked against WCAG 2.2 AA in both modes.
* Self-hosted type: EB Garamond for headings, Sabon Next LT for reading, Special Elite for small labels.
* A sort bar with Latest, Last 7 days, This month and All time, ranked by comment count.
* A description meta tag on every page and JSON-LD structured data (WebSite, Article, BreadcrumbList, VideoObject, Person, ProfilePage). It switches itself off when Yoast SEO, Rank Math, AIOSEO or SEOPress is active.
* `sameAs` profile links and a Mastodon handle, so search engines and the fediverse can connect this site to your other profiles.
* A tabbed settings page that saves without a reload, and still saves with JavaScript off.
* Accessibility work throughout: a skip link, visible focus, a focus-trapped lightbox, dropdown menus you can use with a keyboard or a touch screen, 24px minimum targets, reduced-motion support and Windows high-contrast mode.

The theme ships its own fonts and icons and loads nothing from a CDN. Avatars come from Gravatar, which is part of core WordPress; you can switch them off under Settings → Discussion.

== Installation ==

1. In WordPress, go to Appearance → Themes → Add New → Upload Theme. Choose `ipin-modern-5.0.0.zip`, click Install Now, then Activate.
2. Open Appearance → iPin Settings to pick a colour scheme, write the homepage hero and add your social profiles.
3. Assign a menu to Top Navigation under Appearance → Menus.
4. If you write Sideblog articles, copy `companion/ipin-sideblog/` from the theme folder into `wp-content/plugins/` and activate iPin Sideblog. Your articles then stay put if you ever switch themes.

To install over FTP, unzip the file, upload the `ipin-modern/` folder to `wp-content/themes/`, and activate it under Appearance → Themes.

== Frequently Asked Questions ==

= Do I need any plugins? =
No. Everything ships with the theme. The iPin Sideblog plugin in `companion/` only matters if you publish Sideblog articles.

= How do I make a video pin? =
Edit the post and paste a link into the Video pin box in the sidebar: a direct file link that ends in .mp4 or .webm, or a YouTube or Vimeo link. The box tells you straight away whether the link will play. The featured image becomes the video's cover. Some sites refuse to be embedded in frames. Their videos still play here from the file link.

= Can I open the lightbox from the keyboard? =
Yes. Tab to a pin's title and press Shift+Enter. Plain Enter opens the post itself.

= How do I add a colour scheme? =
Add a `[data-scheme="yourscheme"]` block to `assets/css/tokens.css` with the source colours (the vivid, accent, fill, tint and surface tokens), and a `[data-scheme="yourscheme"][data-theme="dark"]` block for dark mode. Gradients, borders and shadows work themselves out from those. Then add the scheme to `ipin_colour_schemes()` in `inc/admin-options.php` and its browser-bar colours to `ipin_theme_colors()` in `inc/enqueue.php`.

= How does the sort bar work? =
It re-orders the grid by comment count for the chosen period. It sits on the homepage, or on your Posts page if you use a static front page. WordPress already counts comments, so nothing new goes into the database.

= Does it work with the block editor? =
Yes. The editor loads the same fonts and colour tokens as the front end.

= Can I use a child theme? =
Yes. Copy any template into the child theme to override it, or redefine tokens in a `:root { }` block in the child theme's stylesheet.

= What PHP version do I need? =
PHP 8.0 or later.

= Is it translation-ready? =
Yes. Strings use the `ipin-modern` text domain, and `languages/ipin-modern.pot` ships with the theme. To regenerate it: `wp i18n make-pot . languages/ipin-modern.pot --domain=ipin-modern`

== Changelog ==

= 5.0.0 =
The front end is rebuilt. Full details are in CHANGELOG.md.

* Added: homepage hero and featured-pin panel, video pins, a full-screen lightbox, structured data with sameAs, a Mastodon handle, feed enclosures, self-hosted fonts, keyboard and touch dropdown menus.
* Changed: jQuery and its three grid plugins are replaced by a small grid engine in plain JavaScript. Colour tokens are rebuilt in OKLCH, with every pairing checked to WCAG 2.2 AA. Lightbox data comes from a cacheable REST route. The text domain is now `ipin-modern`.
* Removed: sidebars and their page templates, the Ads tab, the Popular Posts widget, and the Font Awesome and Google Fonts CDNs.
* Fixed: a fatal error on the blog home, archives and search; dropdown menus; settings that saved but did nothing; dark-mode contrast on buttons; lightbox data leaking from password-protected posts; and the rest of the 5.0 code review.

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

= 5.0.0 =
Major release. Sidebars, the Ads tab and the Popular Posts widget are gone, and the text domain is now ipin-modern. Read UPGRADING.md before updating a customised site or a child theme.

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
