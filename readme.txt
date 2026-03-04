iPin Modern  —  v4.0
====================
A bold, colorful Pinterest-inspired masonry theme for WordPress.
PHP 8.0+  •  Bootstrap-free  •  Modular CSS  •  WCAG 2.2 AA  •  Zero third-party JS dependencies


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 CONTENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  1.  Overview
  2.  Requirements
  3.  Installation
  4.  Directory Structure
  5.  Admin Settings
  6.  Colour Schemes
  7.  Dark Mode
  8.  Popular Posts & Sort Bar
  9.  Ads
  10. Sideblog (ipin_article)
  11. Lightbox
  12. Share Buttons
  13. JavaScript Libraries
  14. PHP Compatibility
  15. Accessibility (WCAG 2.2 AA)
  16. Developer Notes
  17. License


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 1. OVERVIEW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
iPin Modern is a complete ground-up rewrite of the original iPin
Pinterest-clone theme. It preserves the masonry grid aesthetic while
replacing every line of legacy code:

  • Bootstrap 2/3 removed — replaced with native CSS (Flex + Grid)
  • jQuery Masonry, ImagesLoaded, and InfiniteScroll replaced with
    custom in-house implementations (zero external JS dependencies)
  • PHP rewritten to PHP 8.x with strict types throughout
  • Critical malware removed (remote backlinks.com HTTP injection)
  • Full WCAG 2.2 AA accessibility compliance, contrast verified
  • Modular CSS architecture: 8 purpose-specific files
  • 5 colour schemes with flash-free dark mode
  • Tabbed admin settings page with AJAX save
  • Popular posts sort bar (Latest / This week / This month / All time)
  • Frontpage lightbox with keyboard navigation and focus trap
  • Social share buttons (Pinterest, X, Facebook, Copy link)
  • Sideblog via ipin_article custom post type
  • Ad slots: 8 configurable positions (header, grid, single post)


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 2. REQUIREMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  • WordPress 6.3 or later
  • PHP 8.0 or later
  • MySQL 5.7 / MariaDB 10.3 or later
  • No plugins required
  • No external JS dependencies (all libraries are bundled)


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 3. INSTALLATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  1. Upload the ipin-modern/ folder to /wp-content/themes/
  2. Go to wp-admin → Appearance → Themes → Activate "iPin Modern"
  3. Go to Appearance → iPin Settings to configure
  4. Optionally assign a menu: Appearance → Menus → Top Navigation
  5. Optionally add widgets: Appearance → Widgets → Right/Left Sidebar

  No JS libraries to download separately — everything is bundled.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 4. DIRECTORY STRUCTURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ipin-modern/
│
├── style.css                         WordPress theme header (no CSS rules)
├── functions.php                     Bootstrap: theme setup, sidebars, /inc/ loader
│
├── Template files
│   ├── header.php                    Skip link, navbar, scheme init script
│   ├── footer.php                    Footer widgets, scroll-to-top button
│   ├── index.php                     Masonry grid + popular posts sort bar
│   ├── single.php                    Single post + ad slots + share buttons
│   ├── page.php                      Standard page with right sidebar
│   ├── page_full_width.php           Page template: Full Width
│   ├── page_left_sidebar.php         Page template: Left Sidebar
│   ├── comments.php                  Comments list + form
│   ├── sidebar-left.php              Left sidebar widget area
│   ├── sidebar-right.php             Right sidebar widget area
│   ├── searchform.php                Accessible search form
│   └── 404.php                       Error page
│
├── inc/                              All PHP logic (nothing lives in functions.php)
│   ├── template-tags.php             Helpers, comment callback, RSS filter,
│   │                                 ipin_lightbox_data AJAX handler
│   ├── nav-walker.php                Ipin_Nav_Walker + accessible nav filters
│   ├── post-types.php                ipin_article CPT (Sideblog)
│   ├── popular-posts.php             ipin_get_popular_posts(), pre_get_posts hook,
│   │                                 iPin_Popular_Posts_Widget
│   ├── ads.php                       Slot definitions, ipin_ad(), ipin_grid_ad_at()
│   ├── enqueue.php                   wp_enqueue_style/script, dynamic CSS,
│   │                                 flash-free scheme/dark-mode init
│   ├── admin-options.php             Tabbed settings page + AJAX save handler
│   └── customizer.php                WP Customizer (logo, background)
│
├── assets/
│   ├── css/
│   │   ├── tokens.css                All CSS custom properties, 5 colour schemes,
│   │   │                             dark mode overrides, --focus-ring variable
│   │   ├── base.css                  Reset, typography, skip link, sr-only,
│   │   │                             reduced-motion, forced-colors, prefers-contrast
│   │   ├── nav.css                   Navbar, dropdowns, hamburger, social icons,
│   │   │                             dark mode toggle
│   │   ├── masonry.css               Grid cards, hover bar, loaders, pagination,
│   │   │                             popular posts sort bar
│   │   ├── single.css                Post layout, sidebar, comments, scroll-to-top,
│   │   │                             share buttons bar
│   │   ├── lightbox.css              Lightbox overlay, panels, share buttons
│   │   ├── admin.css                 Admin settings page (wp-admin only)
│   │   └── editor-style.css          Block editor matching styles
│   │
│   ├── js/
│   │   ├── ipin.custom.js            Dark mode, hamburger, scroll-to-top,
│   │   │                             masonry init, infinite scroll, copy-link
│   │   ├── ipin.admin.js             Tabs, swatch picker, AJAX save
│   │   ├── lightbox.js               Open/close, prev/next, keyboard nav,
│   │   │                             focus trap, AJAX data load, share buttons
│   │   ├── jquery.masonry.min.js     iPin custom masonry engine v1.0.0
│   │   ├── jquery.imagesloaded.min.js  iPin custom images-loaded detector v1.0.0
│   │   └── jquery.infinitescroll.min.js  iPin custom infinite scroll v1.0.0
│   │
│   ├── img/                          Reserved for theme images
│   └── fonts/                        Reserved for self-hosted fonts
│
├── languages/                        Translation-ready (.pot template welcome)
├── template-parts/                   Reserved for future partial templates
├── _README.txt                       This file
├── CHANGELOG.md                      Full version history
└── UPGRADING.md                      Migration guides between major versions


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 5. ADMIN SETTINGS  (Appearance → iPin Settings)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚙️  General
    • Comments to show per grid card (0 = hide)
    • Posts per page
    • Show author avatars in grid
    • Footer text (HTML allowed)

🎨  Appearance
    • Colour scheme (5 swatches: Vivid, Ocean, Ember, Forest, Mono)
    • Dark mode default (on / off / follow OS)
    • Card width (140–400 px slider)
    • Rounded card corners toggle

🔗  Social
    • Twitter/X, Facebook, Instagram URLs
    • RSS icon toggle in navbar

💰  Ads
    • Top Header Banner (every page, full-width)
    • Grid slots 1–5 (injected after pins 5, 10, 15, 20, 25)
    • Single post: Above Featured Photo
    • Single post: Below Featured Photo
    Paste any ad HTML (AdSense, Amazon, etc.). Leave blank to disable.

📐  Layout
    • Sidebar position on single posts/pages (right / left / none)

All changes save via AJAX — no full page reload required.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 6. COLOUR SCHEMES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Schemes are defined in assets/css/tokens.css as
[data-scheme="name"] { } overrides on :root:

  vivid   Hot pink / violet / electric blue  (default)
  ocean   Teal / cyan / navy
  ember   Orange / coral / crimson
  forest  Sage green / moss / amber
  mono    Near-black / charcoal / warm grey

Each scheme carries separate light-mode and dark-mode accent values.
Light accents are dark-on-light (≥ 4.5:1). Dark accents are
light-on-dark (≥ 4.5:1). Vivid decorative colours (gradients,
borders) are kept separate from accessible interactive colours.

To add a custom scheme:
  1. Add [data-scheme="myscheme"] { } to tokens.css
  2. Add [data-theme="dark"][data-scheme="myscheme"] { } override
  3. Add a $schemes entry in inc/admin-options.php


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 7. DARK MODE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Applied via data-theme="dark" on <html>. A synchronous <script>
in <head> at priority 1 reads localStorage before any CSS renders —
zero flash of wrong theme on page load.

Toggle behaviour:
  • Writes 'dark' or 'light' to localStorage
  • Updates aria-pressed and aria-label on the toggle button
  • Swaps moon ↔ sun icon
  • Reacts to prefers-color-scheme OS changes in real time


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 8. POPULAR POSTS & SORT BAR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
A sticky sort bar sits above the grid on the homepage:

  Latest       Chronological (default)
  This week    Most commented — last 7 days   (?popular=7days)
  This month   Most commented — last 30 days  (?popular=30days)
  All time     Most commented ever            (?popular=all)

Sorting uses WordPress's native comment_count — no extra DB tables.

The iPin_Popular_Posts_Widget is available in Appearance → Widgets.
Configure title, count, and period independently per widget instance.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 9. ADS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Render a slot in any template:
  <?php ipin_ad( 'ipin_ad_header' ); ?>
  <?php ipin_ad( 'ipin_ad_above_photo' ); ?>
  <?php echo ipin_grid_ad_at( $card_index ); ?>  // inside the grid loop

Blank slots produce zero markup — no empty wrappers added.
Grid ad divs carry .thumb so masonry treats them as regular items.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 10. SIDEBLOG  (ipin_article)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
A second post type for short editorial content alongside the pin grid.
  Single:  /article/{slug}/
  Archive: /articles/
  Admin:   wp-admin → Sideblog

Supports: title, editor, author, thumbnail, excerpt, comments, REST API.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 11. LIGHTBOX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Clicking any grid card opens a lightbox overlay. Features:
  • Two-panel layout: image (left) + info (right); stacks on mobile
  • YouTube, Vimeo, SoundCloud video embed support
  • Prev/Next buttons + left/right arrow key navigation
  • Escape key and click-outside close
  • Full focus trap (Tab / Shift-Tab stays inside dialog)
  • Returns focus to the triggering card on close
  • aria-modal, aria-labelledby, aria-busy
  • Pin data loaded via AJAX — no hidden JSON on page load
  • Share buttons: Pinterest, X, Facebook, View full post


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 12. SHARE BUTTONS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Appear on single posts and inside the lightbox.
Platforms: Pinterest, X (Twitter), Facebook, Copy Link.
Copy Link uses the Clipboard API with execCommand fallback.
Labels hidden on screens < 480 px (icons only).


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 13. JAVASCRIPT LIBRARIES  (all custom, iPin-authored)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
jquery.imagesloaded.min.js  v1.0.0
  Fires a callback once every <img> in a container has settled.
  Proxy Image pattern avoids touching DOM element rendering.
  Supports $(el).imagesLoaded(fn) and deferred .done(fn) forms.

jquery.masonry.min.js  v1.0.0
  Shortest-column bin-packing. Positions via getBoundingClientRect.
  appended() extends existing column heights without full re-layout.
  Debounced resize handler. fitWidth centres grid via margin: auto.
  Methods: init, layout, appended, destroy.

jquery.infinitescroll.min.js  v1.0.0
  Scroll-proximity trigger (300 px from bottom, 120 ms debounce).
  Fetches next page URL, parses in detached div, extracts items.
  Advances next-page href for each subsequent fetch (walks pages).
  Shows loading/finished indicators. Removes pagination on last page.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 14. PHP COMPATIBILITY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Requires PHP 8.0+. Every file: declare(strict_types=1).
Features used: union types, match expressions, nullsafe ??=,
arrow functions (static fn() =>), named array syntax,
typed return types and parameters throughout.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 15. ACCESSIBILITY — WCAG 2.2 AA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  1.1.1  Non-text Content      alt="" decorative, meaningful alt informative
  1.3.1  Info and Relationships  Semantic HTML throughout
  1.3.6  Identify Purpose      Landmarks: main, nav, aside, footer + aria-label
  1.4.3  Contrast (Minimum)    All text ≥ 4.5:1, verified by Python script
  1.4.4  Resize Text           em/rem units, no fixed px font sizes
  1.4.6  Contrast (Enhanced)   prefers-contrast: more supported
  1.4.11 Non-text Contrast     Focus rings and borders ≥ 3:1
  1.4.12 Text Spacing          No overrides blocking user text spacing
  2.1.1  Keyboard              All elements keyboard-operable, Escape closes
  2.3.3  Animation             prefers-reduced-motion fully respected
  2.4.1  Bypass Blocks         Skip link → #main-content
  2.4.3  Focus Order           Logical DOM order, focus returned after close
  2.4.6  Headings and Labels   All forms labelled, headings descriptive
  2.4.7  Focus Visible         :focus-visible on all interactive elements
  2.4.11 Focus Appearance      ≥ 2 px, offset, area requirement met
  2.5.8  Target Size           All targets ≥ 24×24 px, nav buttons 44×44 px
  4.1.2  Name, Role, Value     aria-pressed, aria-expanded, aria-label dynamic

  Plus: forced-colors (Windows HCM), role="list" on styled <ul> (VoiceOver),
        <time datetime="…"> on dates, aria-busy on masonry container.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 16. DEVELOPER NOTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Override any CSS token in a child theme:
  :root { --card-width: 260px; --clr-accent-1: #005A9E; }

PHP data passed to JS via wp_localize_script as window.ipinData:
  ajaxUrl, themeUrl, darkModeDefault, colourScheme,
  cardWidth, roundedCards, allLoaded

Ad slots in custom templates:
  <?php ipin_ad( 'ipin_ad_header' ); ?>

Generate a translation template:
  wp i18n make-pot . languages/ipin.pot --domain=ipin


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 17. LICENSE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
GNU General Public License v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

All bundled JavaScript is original work, GPL v2+ licensed.
Google Fonts: SIL Open Font License.
Font Awesome 6 Free: icons CC BY 4.0, fonts SIL OFL 1.1.
