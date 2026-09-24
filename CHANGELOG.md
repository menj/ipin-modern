# Changelog

All notable changes to **iPin Modern** are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

## [5.0.0] - 2026-09-24 - Front-end rebuild

The front end is rebuilt around the 5.0 design: a statement hero, video pins,
a full-screen lightbox and new type, on a grid engine with no jQuery. It came
out of a full code review of 4.1.4, and every finding from that review is
either fixed here or retired with the feature it belonged to.
Read [UPGRADING.md](UPGRADING.md) before updating a customised site.

### Added
- Homepage hero: your heading (wrap one word in `*asterisks*` for the gradient
  accent) and a lede paragraph, set in Settings → Layout. Falls back to the
  site title and tagline. Shows on the first page only.
- Featured-pin panel beside the hero: the first sticky post (else the newest
  pin with an image), board stats and category links. Has its own toggle.
- Video pins. A "Video pin" box in the post editor takes a direct file link
  (.mp4, .webm, .ogv, .m4v, .mov) or a YouTube, youtu.be, Shorts or Vimeo
  link, and says whether the link will play. Files play in a native `<video>`
  with the featured image as poster; YouTube uses youtube-nocookie.com. Cards
  get a play badge, single posts show the player, and `VideoObject` schema
  is emitted. Existing `_ipin_video_embed_url` meta keeps working.
- `inc/seo.php`: a description meta tag per page, and JSON-LD for `WebSite`,
  `Article`, `BreadcrumbList`, `VideoObject`, `Person` (with `sameAs`) and
  `ProfilePage`. Stands down when Yoast SEO, Rank Math, AIOSEO or SEOPress
  is active.
- Settings → Social: an "Also-me profile URLs" list for `sameAs`, and a
  Mastodon handle that outputs `fediverse:creator` and a `rel="me"` link.
- RSS enclosures: the video file for video pins, otherwise the featured image
  at `large`, with the byte length of that exact file.
- Self-hosted fonts in `assets/fonts/` (WOFF2): EB Garamond (display), Sabon
  Next LT (body), Special Elite (labels, new `--font-accent` token).
- Pin mark next to the site name and as the default favicon (`favicon.svg`
  plus a 16/32/48 `favicon.ico`), printed only when no Site Icon is set.
- Sun/moon pill switch for dark mode.
- Keyboard-operable dropdown menus: parent items get a disclosure button
  with `aria-expanded`; Escape closes the open submenu.
- `ipin/v1/pin/{id}` REST route for lightbox data.
- `companion/ipin-sideblog/`: the Sideblog post type as a standalone plugin.
- Cards are CSS size containers, so their type follows the card width setting.
- Cards ease in on a CSS scroll-driven timeline (off under reduced motion).
- Cross-document View Transitions: a card's image morphs into the post's
  featured image in browsers that support it.
- `languages/ipin-modern.pot`.

### Changed
- jQuery and the three bundled plugins (`jquery.masonry`, `jquery.imagesloaded`,
  `jquery.infinitescroll`) are replaced by `assets/js/ipin.grid.js`. Layout
  runs immediately from the images' width and height, so `loading="lazy"`
  works; columns are recalculated on resize and rotation; the next page loads
  through `fetch()`. Without JavaScript the grid falls back to CSS columns and
  the pagination links.
- `ipin.custom.js` and `lightbox.js` are rewritten without jQuery.
- Lightbox fills the viewport and shows the whole image (`object-fit: contain`).
- `tokens.css` is regenerated in OKLCH. Every text and control pairing is
  checked to WCAG 2.2 AA for all 5 schemes in light and dark mode, with the
  ratios in comments. Schemes now set only source colours; gradients, washes,
  borders, shadows and the focus ring derive from them. New tiers:
  `--clr-fill-*` for solid controls under white labels and `--grad-text` for
  gradient headings.
- Settings are declared once in `ipin_settings_schema()`. The AJAX save and a
  new no-JS save through `options.php` share the same sanitisers. Without
  JavaScript every settings section shows stacked.
- Card width and page size are clamped; the colour scheme is validated.
- Grid images use a new `ipin-card` size (800px) with `srcset`.
- `single.css` loads only on posts and pages; `masonry.css` and
  `lightbox.css` only on grid views. Footer, search form and scroll-to-top
  styles moved to `base.css`.
- Sort-bar links are built on the Posts page URL, so they work with a static
  front page.
- Admin colour swatches read the real scheme gradients from `tokens.css`.
- `theme-color` follows the active scheme and switches for dark mode.
- Text domain renamed from `ipin` to `ipin-modern` to match the theme folder.
- Social icons in the nav and share bar come from a bundled SVG set.

### Removed
- Sidebars: both widget areas, `sidebar-left.php`, `sidebar-right.php`,
  `page_left_sidebar.php`, `page_full_width.php` and the sidebar setting.
- The ads system: `inc/ads.php` and the Ads tab. Saved `ipin_ad_*` options are
  left in the database.
- The Popular Posts widget (no widget areas remain to hold it).
- Font Awesome and Google Fonts CDN requests. The theme loads nothing from
  other servers; avatars still come from Gravatar through core.
- `admin-ajax` lightbox handler (replaced by the REST route).

### Fixed
- `index.php` had a fatal parse error in the image-fallback regex, so the
  blog home, archives and search pages all failed.
- The nav walker never ran (`??=` against defaults that are not null), so
  submenus rendered unstyled and always open.
- Card width and rounded corners had no effect: `tokens.css` printed after the
  override and won. The override now goes through `wp_add_inline_style()`.
- "Posts per page" and "Show author avatars on grid cards" were saved but
  never used.
- Dark mode: white labels on the active sort pill measured 2.39:1. Filled
  controls now keep 5:1 labels and at least 3:1 against the page.
- Gradient headings started on decorative colours as low as 1.9:1.
- Footer text was about 4.0:1; now at least 6.5:1. Muted text on inputs was
  4.42:1 in three schemes.
- Lightbox: prev/next broke after the first page of pins, video pins kept the
  loading spinner over the player, keyboard focus could leave the dialog, and
  a failed load left a blank dialog.
- Dark-mode choice was saved on every page view, so visitors stopped
  following their OS setting.
- Copy-link lost its label after a quick double click.
- Duplicate `id="comments"`; the reply form's `h2` sat inside core's `h3`;
  the comments heading printed the post title unescaped.
- Author comments were painted white-on-purple; they now get a thin rule and
  an "Author" badge.
- Name, Email and Website sit in the three-column grid the CSS always
  expected.
- Search pages called `category_description()`.
- The comment submit button had no label colour, border or font styles.
- `add_editor_style()` had no effect without `editor-styles` support.
- The Customizer notice section had no controls, so WordPress never showed it.
- Nested links inside each card made browsers restructure the markup.
- `--dur-fast` was used but never defined, so three button transitions never ran.
- Lightbox data was HTML-escaped for code that writes plain text, so `&`
  showed as `&amp;`.

### Security
- The lightbox endpoint returned data for password-protected and
  non-public posts. The REST route refuses both.
- Video URLs were concatenated into iframe HTML. The lightbox now builds all
  markup with DOM properties, and URLs are scheme-checked on both ends.

### Performance
- Grid comment previews come from one query per page instead of one per card
  (72 → 60 queries on a 12-card homepage).
- The fallback card image is cached in post meta instead of queried per card.
- Lightbox responses are cacheable (`Cache-Control: public, max-age=300`) and
  remembered in the page.
- No render-blocking scripts in `<head>`.

### Bumped
- `style.css`: Version 4.1.4 → 5.0.0; added Requires at least 6.5, Tested up
  to 7.1, Requires PHP 8.0.

---

## [4.1.4] - 2026-03 - Admin asset fix

### Fixed
- The admin assets were enqueued twice: `ipin_admin_scripts()` in
  `admin-options.php` was a stale copy of `ipin_enqueue_admin_assets()` in
  `enqueue.php`, and both ran on `admin_enqueue_scripts`. The stale copy is
  removed.

---

## [4.1.3] - 2026-03 - Lightbox and cache-busting fixes

### Fixed
- The lightbox never opened: cards were missing `data-post-id`, so no post IDs
  were collected.
- All assets were versioned as "3.0", so browsers kept old files after
  updates. Versions now come from the theme header.
- `add_editor_style()` ran on `wp_enqueue_scripts` and never reached the
  editor. Moved to `after_setup_theme`.
- Dropdown lists carried `role="menu"`, which promises keyboard behaviour the
  nav doesn't have. Removed.
- The custom logo height hint (50px) now matches the CSS (52px).
- The sort bar said "This week" while the widget said "Last 7 days". Both now
  read "Last 7 days".

---

## [4.1.2] — 2026-03 — Navigation Bar Redesign

User-facing redesign of the top navigation bar for a bolder, more airy feel.

### Changed — `assets/css/nav.css` + `assets/css/tokens.css`
- `--nav-height` token: `64px` → `96px`. Body `padding-top` and the mobile
  dropdown `top` offset both derive from this token and update automatically.
  **Child themes** using `var(--nav-height)` for vertical positioning should
  be reviewed.
- `.nav-inner` horizontal padding: `24px` → `40px` each side.
- `.nav-inner` element gap: `12px` → `28px`.
- `.navbar-brand` font-size: `1.4rem` → `1.75rem`; added `letter-spacing: -.01em`.
- Custom logo image height (`.navbar-brand--logo img`): `40px` → `52px`.
- Nav link padding: `6px 13px` → `9px 18px`; font-size `0.875rem` → `0.95rem`;
  font-weight `600` → `700`.
- Search bar input width: `130px` → `180px`; padding and button enlarged.
- Social icon and dark-mode toggle size: `44px` → `48px`; font-size `1rem` → `1.1rem`.
- Icon group gap: `2px` → `6px`.
- Nav border-bottom: `2px` → `3px`.
- Nav `box-shadow`: `shadow-sm` → `shadow-md`.
- Mobile drawer padding: `16px` → `20px`; gap `10px` → `12px`.

### Bumped
- `style.css`: Version 4.1.1 → 4.1.2

---

## [4.1.1] — 2026-03 — WCAG 2.2 AA Audit Fixes

Manual WCAG 2.2 AA audit conducted across all templates, CSS and JS. Five
issues found and resolved.

### Fixed — `header.php` (WCAG 4.1.1 Parsing)
- **Nested `<a>` elements when a custom logo is active.** `the_custom_logo()`
  already outputs `<a href="..."><img></a>`, but the theme wrapped it in a
  second `<a class="navbar-brand">`. Nested anchors are invalid HTML and
  produce undefined behaviour in AT. Fixed: custom logo is now wrapped in a
  `<span class="navbar-brand navbar-brand--logo">` instead. The text fallback
  (no custom logo) still uses the `<a>` wrapper as before.

### Fixed — `inc/admin-options.php` (WCAG 4.1.2 Name, Role, Value)
- **`aria-hidden="true"` on `.ipin-scheme-card__inner`** made the scheme name
  invisible to screen readers. The containing `<label>` had no other text, so
  the radio button had an empty accessible name. Removed `aria-hidden`.

### Fixed — `assets/js/lightbox.js` (WCAG 2.1.2 No Keyboard Trap)
- **Close, Previous, and Next buttons were appended to `<body>`**, outside the
  `$overlay` element that `trapFocus()` operated on. Tab could escape the open
  dialog. All three buttons are now appended inside `$overlay` so the focus
  trap correctly contains them.

### Fixed — `assets/js/lightbox.js` (Bug)
- **`hideLlighting()` typo** — the function called on AJAX failure did not
  exist. The loading spinner never cleared when a pin failed to load. Corrected
  to `hideLoading()`.

### Fixed — `assets/js/ipin.custom.js` + `footer.php` (WCAG 4.1.3 Status Messages)
- **"Copied!" confirmation was not announced to screen readers.** A
  `role="status" aria-live="polite"` region (`#ipin-live-region`) is now
  present in `footer.php`. The copy-link handler writes the confirmation text
  to it so AT announces "Link copied to clipboard." without moving focus.

### Confirmed passing (no changes needed)
- Skip link (WCAG 2.4.1) ✓
- `<main id="main-content" tabindex="-1">` skip target ✓
- `<nav aria-label>` landmarks ✓
- All toggle controls: `aria-pressed`, `aria-expanded` updated by JS ✓
- Focus trap closes on Escape + returns focus to trigger ✓
- `prefers-reduced-motion` respected in base.css and lightbox.css ✓
- `forced-colors: active` overrides in base.css ✓
- `prefers-contrast: more` overrides in base.css ✓
- All text contrast ratios (tokens.css) ≥ 4.5:1 in both light + dark ✓
- Touch targets ≥ 44×44 px on all interactive nav elements ✓
- `<time datetime="...">` on post dates ✓
- `<img alt="...">` — featured image gets post title as alt ✓
- Infinite scroll announces grid-ready via `aria-busy="false"` ✓

### Bumped
- `style.css`: Version 4.1.0 → 4.1.1

---

## [4.1.0] — 2026-03 — Design System UI

Complete rewrite of the admin settings UI to the `modern_settings_ui` design
system spec v1.0.0.

### Changed — `assets/css/admin.css`
- All styles now scoped exclusively to `.plugin-settings-root` — zero rules
  outside the wrapper, no `:root` overrides, no generic-tag selectors.
- Tokens renamed to `--plugin-accent`, `--plugin-surface`, `--plugin-border`,
  `--plugin-focus` — inheriting from `--wp-admin-theme-color` as the default
  accent so the UI automatically matches the WP admin colour scheme.
- Internal shorthand tokens use `--ipin-` prefix and are also wrapper-scoped.
- No hardcoded hex palette is set as a default — existing host colours are
  fully preserved.

### Changed — `inc/admin-options.php` render function
- Root wrapper class changed to `.plugin-settings-root` (spec requirement).
- All boolean controls (show avatars, dark mode, rounded cards, RSS, manual
  ads, per-slot enabled) converted from plain checkboxes to the
  `.ipin-switch` toggle component (track + thumb + CSS transition).
- Colour scheme picker converted to `.ipin-scheme-grid` of visual selector
  cards — each scheme is a clickable card with a gradient swatch preview.
- Card width control converted to `input[type="range"]` slider with a live
  `<span aria-live="polite">` value display.
- All sections grouped into `.ipin-card` containers.
- Tab buttons now use `ipin-active` class and correct `tabindex` management
  for ARIA roving-tabindex keyboard pattern.
- Version badge reads live from `wp_get_theme()->get('Version')`.
- Footer added: "Developed by MENJ" + GitHub link, styled with
  `--plugin-accent` link colour and top divider.
- Emoji icons replaced with HTML entity codes to avoid encoding issues.

### Changed — `assets/js/ipin.admin.js`
- All event binding scoped to `.plugin-settings-root` — no global listeners.
- Tab switching implements ARIA roving tabindex with ArrowLeft/ArrowRight
  keyboard navigation as required by the ARIA Tabs pattern.
- Colour scheme radio change updates `aria-checked` on `.ipin-scheme-card__inner`.
- Range slider live-updates `#ipin_card_width_val` on `input` event.
- Global ads switch and per-slot toggles use `ipin-active` / `ipin-visible`
  classes consistent with the CSS system.
- AJAX save uses `fetch()` + `FormData`, restores original button label on
  completion or error. No dependency on `admin`-only jQuery patterns.

### Version bump
- `style.css`: Version 4.0.2 → 4.1.0

---

## [4.0.2] — 2026-03 — Manual Ad Slot Toggles

### Added
- **Global manual ads switch** in the Ads admin tab. One click disables
  all manual slot output without deleting any code — ideal for handing
  placement over to Google Site Kit Auto Ads and switching back later.
- **Per-slot enable/disable checkbox** on each of the 8 ad slots.
  Each slot shows an Active / Paused badge that updates live. Paused
  slots are visually dimmed in the admin; their code is preserved.
- When the global switch is off, the per-slot section is dimmed and
  non-interactive to make the hierarchy clear.
- `ipin_manual_ads_on()` and `ipin_ad_slot_enabled()` helpers in
  `inc/ads.php`. Both `ipin_render_ad()` and `ipin_get_grid_ads()`
  now check both conditions before outputting any HTML.
- `ipin_manual_ads_enabled` and `{slot}_enabled` options registered
  and saved through the AJAX save handler.
- Toggle and badge styles added to `assets/css/admin.css`.
- Live badge/dim JS added to `assets/js/ipin.admin.js`.

### Bumped
- `style.css`: Version 4.0.1 → 4.0.2

---

## [4.0.1] — 2026-03 — Google Site Kit / AdSense Compatibility

### Fixed
- **Ad slots were never actually saved.** The AJAX save handler
  (`ipin_ajax_save_options`) did not include ad slot fields — they were
  registered via the Settings API (`register_setting`) but the form
  submits via AJAX, bypassing that path entirely. All eight ad slot
  values now save correctly on every settings page submission.
- **`wp_kses_post` was stripping AdSense code.** The previous sanitizer
  removed `<script>` tags and `data-*` attributes from `<ins>` elements,
  silently discarding all pasted ad code. Replaced with a new
  `ipin_sanitize_ad_code()` function in `inc/ads.php` that preserves
  the raw content for `manage_options` users and returns an empty string
  for anyone else (defence-in-depth — only admins can reach the field).

### Added
- **Google Site Kit compatibility.** The theme already had `wp_head()`
  and `wp_body_open()` in the correct positions, so Site Kit's script
  injection and site verification work out of the box. The Ads tab info
  box now explains both paths:
  - **Auto Ads**: install Site Kit, connect AdSense, enable Auto Ads —
    the script is injected automatically, no slot code needed.
  - **Manual units**: paste the full `<ins>` + `<script>` block from
    AdSense directly into any slot — script tags are now preserved.
- Ads tab placeholder text encoding fixed (garbled UTF-8 em-dash
  replaced with plain ASCII double-dash).

---

## [4.0.0] — 2026-03 — Design Edition

The fourth major release. Adds every visual feature from the iPin Pro
spec — sort bar, lightbox, share buttons, sideblog, ads — while keeping
the codebase free of social-network features (no custom DB tables,
no user authentication, no upload pipelines).

### Added
- **Popular posts sort bar** on the homepage grid with four modes:
  Latest, This week (7 days), This month (30 days), All time.
  Ranks by WordPress native `comment_count` — zero extra DB tables.
- **`iPin_Popular_Posts_Widget`** for sidebars: configurable title,
  post count, and period (all time / 30 days / 7 days).
- **Frontpage lightbox** (`lightbox.js` + `lightbox.css`): opens on
  card click or Enter key. Two-panel layout (image + info). YouTube,
  Vimeo, SoundCloud video embed. Prev/Next + arrow key navigation.
  Escape/click-outside close. Full ARIA focus trap. Pin data loaded
  via `ipin_lightbox_data` AJAX action — no hidden JSON on page load.
- **Social share buttons** on single posts and inside the lightbox:
  Pinterest, X (Twitter), Facebook, Copy Link. Copy Link uses the
  Clipboard API with `execCommand` fallback. Labels hidden on mobile
  (icons only). "Copied!" feedback with auto-reset.
- **Ad slot system** (`inc/ads.php`):
  - `ipin_ad_header` — full-width banner every page
  - `ipin_ad_grid_1` through `ipin_ad_grid_5` — injected after pins 5,
    10, 15, 20, 25 in the masonry grid
  - `ipin_ad_above_photo` / `ipin_ad_below_photo` — single post
  - Grid ad divs carry `.thumb` so masonry treats them as regular items
  - `ipin_ad()` and `ipin_grid_ad_at()` template helpers
  - Blank slots produce zero markup
- **💰 Ads tab** in admin settings with a textarea per slot.
- **`ipin_article` CPT** (Sideblog): editorial posts alongside the grid.
  Public URL `/article/{slug}/`, archive `/articles/`. Registered on
  `after_switch_theme` with rewrite flush.
- **`inc/popular-posts.php`** — query function, `pre_get_posts` hook,
  and sidebar widget.
- **`inc/ads.php`** — all slot logic, grid injection helper,
  `admin_init` setting registration.
- **`inc/post-types.php`** — `ipin_article` CPT only (design edition).
- **Sort bar styles** in `assets/css/masonry.css` — sticky positioning,
  active state, focus ring.
- **Share bar styles** in `assets/css/single.css` — responsive,
  platform brand colours, mobile icon-only breakpoint.
- **Share button styles** in `assets/css/lightbox.css` — consistent
  with single post share bar.
- **`ipin_lightbox_data` AJAX handler** in `inc/template-tags.php` —
  returns title, permalink, image URL, video embed, author info, date,
  description, source URL, and latest 3 comments. No social counts.
- **Copy-link JS** appended to `ipin.custom.js` — event-delegated click
  handler, Clipboard API, execCommand fallback, animated feedback.

### Changed
- `functions.php` now loads: `post-types.php`, `popular-posts.php`,
  `ads.php` in addition to the previous core set.
- `inc/enqueue.php` now enqueues `lightbox.css` on all pages,
  `lightbox.js` on non-singular pages only.
- Admin tabs expanded from 4 to 5: added 💰 Ads.
- `lightbox.js` updated: social like/repin buttons replaced with share
  buttons (Pinterest, X, Facebook, View full post). `ipinSocial`
  dependency removed; uses `ipinData.ajaxUrl` instead.
- `index.php` now renders the sort bar above the masonry container.
- `single.php` now renders the share bar between the post footer and
  post navigation.

### Removed
- All social-network features stripped: likes, repins, follows,
  user badges, notifications, email notifications, user upload,
  bookmarklet, OAuth login, boards (ipin_board CPT).
- Deleted files: `inc/social-functions.php`, `inc/notifications.php`,
  `inc/bookmarklet.php`, `inc/image-upload.php`, `inc/oauth.php`.
- Deleted CSS: `assets/css/social.css`, `assets/css/upload.css`,
  `assets/css/profile.css`.
- Deleted JS: `assets/js/social.js`, `assets/js/upload.js`.
- Deleted templates: `page-upload.php`, `embed-pin.php`, `author.php`,
  `single-ipin_board.php`, `template-parts/card.php`.
- No custom DB tables (ipin_likes, ipin_repins, ipin_follows,
  ipin_notifications) are created in this edition.

### Custom JS Libraries — rewritten from scratch
All three grid JS libraries were discarded and replaced with
purpose-built, fully commented, iPin-authored implementations.
The public API surface is identical to the Desandro/Metafizzy
originals — all existing call-sites work unchanged.

- **`jquery.imagesloaded.min.js` v1.0.0** — proxy Image pattern,
  jQuery Deferred, handles cached images, supports both callback
  and `.done()` forms.
- **`jquery.masonry.min.js` v1.0.0** — shortest-column bin-packing
  via `getBoundingClientRect`, `appended()` incremental placement,
  debounced resize handler, `fitWidth` centring, `destroy` cleanup.
- **`jquery.infinitescroll.min.js` v1.0.0** — 300 px proximity
  trigger, 120 ms scroll debounce, AJAX page fetch with detached DOM
  parsing, next-href advancement (correctly walks page N+1, N+2, ...),
  loading/finished indicators, pagination nav removal.

### Version bump
- `style.css`: Version 3.0 → 4.0

---

## [3.0.0] — 2025-02 — Assets & Inc Restructure

Third major release. All PHP logic split into dedicated `/inc/` files;
all assets moved under `/assets/`.

### Added
- `/assets/` parent directory containing `/css/`, `/js/`, `/img/`,
  `/fonts/` subdirectories.
- **`inc/template-tags.php`** — `ipin_option()`, `ipin_human_time_diff()`,
  `ipin_comment()` callback, `ipin_comment_form_fields()`,
  `ipin_feed_content()` RSS filter, `ipin_body_classes()`.
- **`inc/nav-walker.php`** — `Ipin_Nav_Walker` extending
  `Walker_Nav_Menu`. ARIA `aria-haspopup`, `aria-expanded` on dropdowns.
  `ipin_nav_css_class()` normalises current-page classes to `.active`.
  `ipin_nav_menu_args()` injects walker into `wp_nav_menu()`.
- **`inc/enqueue.php`** — all `wp_enqueue_style/script` logic extracted
  from `functions.php`. CSS dependency chain enforced. Dynamic CSS
  inline style + synchronous scheme init script.
- **`inc/admin-options.php`** — moved from flat file, updated paths to
  `/assets/css/admin.css` and `/assets/js/ipin.admin.js`.
- **`inc/customizer.php`** — WP Customizer integration; redirects
  default colour section to iPin Settings.
- `functions.php` rewritten as a pure bootstrap file: `ipin_setup()`,
  `ipin_widgets_init()`, and five `require_once` calls.

### Changed
- All CSS/JS paths updated from `css/` and `js/` to `assets/css/`
  and `assets/js/`.
- `ipin_dynamic_css_and_scheme()` moved to `inc/enqueue.php`.
- Previous flat `css/` and `js/` directories removed.
- `_README.txt` updated with full new structure.

---

## [2.0.0] — 2025-02 — Modular CSS & Admin Options

Second major release. Monolithic `style.css` split into modules;
full admin settings page added.

### Added
- **`assets/css/tokens.css`** — CSS custom properties for all colours,
  spacing, typography, animation duration, and z-index layers.
  Five colour schemes as `[data-scheme]` attribute overrides:
  Vivid, Ocean, Ember, Forest, Mono. Dark mode as
  `[data-theme="dark"]` overrides with accessible accent values.
- **`assets/css/base.css`** — CSS reset, typography scale, WP
  alignment classes, utility classes (`.sr-only`, `.container`).
- **`assets/css/nav.css`** — fixed navbar, dropdown animations,
  search form, social icons, dark mode toggle, hamburger menu.
- **`assets/css/masonry.css`** — grid cards, hover action bar, AJAX
  loader spinner, pagination, empty state.
- **`assets/css/single.css`** — content grid, post wrapper, sidebar,
  comments thread, post navigation, scroll-to-top button.
- **`assets/css/admin.css`** — tabbed admin page, field rows,
  colour swatch picker, toggle switch component.
- **`assets/css/editor-style.css`** — block editor styles matching
  the frontend typography and colour tokens.
- **Tabbed admin settings page** (`inc/admin-options.php`):
  4 tabs (General, Appearance, Social, Layout), 13 registered
  settings, swatch colour picker, AJAX save with nonce verification.
- **`assets/js/ipin.admin.js`** — tab switching with sessionStorage
  persistence, swatch selection, AJAX save with loading state.
- **`ipin_dynamic_css_and_scheme()`** — injects `--card-width`,
  `--radius-lg` inline style, plus synchronous scheme + dark-mode
  init script at `wp_head` priority 1 (flash-free).
- **`ipin_get()` helper** — `get_option()` with default fallback.

### Changed
- `functions.php` `ipin_scripts()` updated for CSS dependency order:
  Google Fonts → Font Awesome → tokens → base → nav → single →
  masonry (non-singular) → style → masonry JS libs → ipin-custom.
- `ipin_option()` checks `wp_options` before `theme_mod` for
  backward compatibility with v1 installs.
- Dark mode default now reads from the admin option rather than
  always following the OS preference.

---

## [1.0.0] — 2025-01 — Modern Rewrite (Initial Release)

Full ground-up rewrite of the original iPin theme.

### Security
- **Removed malware**: `header.php` contained a remote HTTP call to
  `backlinks.com` that injected SEO spam links into every page load.
  Call removed; theme audited for similar patterns.

### Added
- PHP 8.x compatibility throughout: strict types, typed parameters,
  return types, arrow functions, match expressions.
- **CSS custom properties** replacing all hardcoded colour values.
- **Dark mode** with `localStorage` persistence, system preference
  detection, flash-free init.
- **Brand identity**: hot pink / violet / electric blue gradient,
  Google Fonts (Plus Jakarta Sans + Syne).
- **Font Awesome 6** replacing Font Awesome 4.
- **Hamburger nav** replacing Bootstrap navbar collapse.
- **Scroll-to-top button** — accessible `<button>`, visible after
  300 px scroll, hidden attribute + aria-hidden.
- **Masonry init** via jQuery plugin (previously third-party only).
- **Infinite scroll** wiring.
- **Dark mode toggle** in navbar.
- All template files modernised: `header.php`, `footer.php`,
  `index.php`, `single.php`, `page.php`, `page_full_width.php`,
  `page_left_sidebar.php`, `404.php`, `comments.php`,
  `sidebar-left.php`, `sidebar-right.php`, `searchform.php`.
- `editor-style.css` for the block editor.
- `ipin.custom.js` — dark mode toggle, hamburger, scroll-to-top,
  masonry init, infinite scroll init.
- Google Fonts loader in `functions.php`.
- Font Awesome 6 CDN in `functions.php`.

### Removed
- Bootstrap 2/3 (`bootstrap.min.css`, `bootstrap.min.js`).
- jQuery UI dependency.
- All `!important` overrides from the original stylesheet.
- Inline `style=""` attributes from templates.
- PHP 5.x compatibility shims.

---

## [0.x] — Original iPin Theme (Pre-rewrite)

The original iPin theme by its respective authors.
Not documented here — see the original theme's readme if available.
The v1.0.0 release above represents the first iPin Modern commit.
