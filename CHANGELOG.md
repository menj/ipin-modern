# Changelog

All notable changes to **iPin Modern** are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

---

## [4.0.0] — 2025-03 — Design Edition

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
