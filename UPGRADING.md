# Upgrading iPin Modern

Migration guides for each major version jump, plus the future roadmap.

## Upgrading to 4.1.2 (from any 4.1.x or 4.0.x)

**Drop-in upgrade** — no settings migration, no database changes, no template changes.

One token changed that may affect custom CSS:

| Token | Old value | New value |
|---|---|---|
| `--nav-height` | `64px` | `96px` |

If your child theme or custom CSS uses `var(--nav-height)` to offset fixed or sticky
elements (hero sections, sticky sidebars, scroll anchors), check and adjust those
rules after upgrading.

All other 4.1.x patches (4.1.0 and 4.1.1) are also drop-in. No action required.

---

## Upgrading from v3.x to v4.0

### What changed

v4.0 adds new files and restructures `functions.php` to load them.
If you are running a **stock install** (no child theme, no custom code),
the upgrade is a simple file replacement with one extra step.

If you have a **child theme or custom code** that references the three
masonry JS libraries by the Desandro/Metafizzy API, read the JS section
below — the API surface is identical so no changes should be needed.

### Step-by-step

1. **Back up your current theme folder** before doing anything.

2. **Replace the theme files.**
   Upload the new `ipin-modern/` folder contents to
   `/wp-content/themes/ipin-modern/`, overwriting all existing files.

3. **No database migration needed.**
   v4.0 (Design Edition) adds no custom DB tables. The ipin_article CPT
   uses the standard WordPress posts table.

4. **Flush rewrite rules.**
   Go to Settings → Permalinks and click Save Changes. This registers
   the new `/article/` and `/articles/` rewrite slugs for `ipin_article`.

5. **Check your ad slots.**
   A new 💰 Ads tab appears in Appearance → iPin Settings. Any ad code
   you previously hard-coded into templates should be moved here.

6. **Verify the sort bar.**
   The homepage now has a sticky sort bar above the grid. If it overlaps
   your nav, adjust `--nav-height` in your child theme's CSS.

### JavaScript libraries

The three grid JS libraries (`jquery.masonry.min.js`,
`jquery.imagesloaded.min.js`, `jquery.infinitescroll.min.js`) have been
replaced with in-house implementations. The public API is identical:

```js
// These call patterns work exactly as before:
$('#masonry').imagesLoaded(function () { ... });
$('#masonry').masonry({ itemSelector: '.thumb', ... });
$('#masonry').masonry('appended', $newItems, true);
$('#masonry').infinitescroll({ navSelector: '...', ... }, callback);
```

If you supplied your own copies of these libs from the Desandro/Metafizzy
packages, you can remove them — the bundled versions are now self-contained
and no external download is required.

### Child theme considerations

- **New CSS files added**: `lightbox.css` (all pages) is now enqueued
  by `inc/enqueue.php`. If your child theme overrides `ipin_enqueue_assets`,
  add `ipin-lightbox` to your dependency array.
- **New inc/ files**: `post-types.php`, `popular-posts.php`, `ads.php`
  are now required in `functions.php`. If you override `functions.php`
  in a child theme, add these three `require_once` calls.
- **Sort bar markup**: `index.php` now contains a `<nav class="sort-bar">`
  above the masonry container. If you override `index.php`, add the sort
  bar block (see the source file for the markup).

---

## Upgrading from v2.x to v3.0

### What changed

v3.0 moved all CSS from `css/` to `assets/css/` and all JS from `js/`
to `assets/js/`. It also split `functions.php` into five `/inc/` files.

### Step-by-step

1. **Back up your theme folder.**

2. **Replace all theme files** with the v3.0 versions.

3. **Remove old directories** if they still exist on your server:
   - `/wp-content/themes/ipin-modern/css/`
   - `/wp-content/themes/ipin-modern/js/`
   These are replaced by `/assets/css/` and `/assets/js/`.

4. **Place the three JS libs** in `assets/js/` (previously they were
   in `js/`). In v4.0 these are bundled — skip this for v4.0.

5. **No database migration needed.**

### Child theme considerations

Any child theme referencing asset URLs must update paths:
- `css/tokens.css` → `assets/css/tokens.css`
- `js/ipin.custom.js` → `assets/js/ipin.custom.js`

If your child theme hooks into `ipin_enqueue_assets`, update any
`get_template_directory_uri() . '/css/'` references to `/assets/css/`.

---

## Upgrading from v1.x to v2.0

### What changed

v2.0 replaced the monolithic `style.css` with modular CSS files and
added the tabbed admin settings page.

### Step-by-step

1. **Back up everything**, especially any custom CSS you added to
   `style.css` — it will be overwritten.

2. **Replace all theme files** with v2.0 versions.

3. **Re-apply any custom CSS** to a child theme stylesheet or add it
   to the appropriate module file (e.g. `assets/css/single.css`).

4. **Configure the admin page**: visit Appearance → iPin Settings.
   Settings previously set via the Customizer are still available but
   are now primarily managed through the admin page.

5. **No database migration needed.**

---

## Installing fresh (no prior version)

See the Installation section in `_README.txt`. Summary:

1. Upload `ipin-modern/` to `/wp-content/themes/`
2. Activate via Appearance → Themes
3. Settings → Permalinks → Save Changes (flush rewrites)
4. Appearance → iPin Settings → configure

---

## Known issues

### Ad slots not saving
- This was a bug fixed in v4.0.1. If you are on v4.0.0, upgrade to
  v4.0.1 — the AJAX save handler was missing all eight ad slot fields.

### AdSense code appears blank after saving
- This was a bug fixed in v4.0.1. `wp_kses_post` was stripping
  `<script>` tags from pasted ad code. Upgrade to v4.0.1.

### Google Site Kit — site not verified
- Confirm `wp_head()` is present in your `header.php`. It is in the
  default theme but custom child themes may have removed it.
- Site Kit verification injects a `<meta>` tag via `wp_head`. If
  verification fails, check that no caching plugin is stripping meta
  tags from the `<head>`.

### Google Site Kit — Auto Ads not showing
- Auto Ads requires the AdSense account to be approved and Auto Ads
  enabled inside the AdSense dashboard, not just in Site Kit.
- The AdSense script is injected via `wp_head()` — if a caching layer
  is serving stale HTML without the script tag, purge the cache after
  connecting Site Kit.

### Masonry layout not initialising
- Confirm jQuery is loading. Go to a front-end page, open browser
  DevTools Console, type `jQuery.fn.jquery` — should show a version.
- Confirm the three JS libs are loading: check the Network tab for
  `jquery.masonry.min.js`, `jquery.imagesloaded.min.js`,
  `jquery.infinitescroll.min.js`.
- Check for JS errors in the Console tab.

### Grid items all stacking vertically
- This means masonry is running before images have dimensions.
  Check that `jquery.imagesloaded.min.js` is loading successfully.

### Infinite scroll not triggering
- Confirm your theme has a `#navigation` element with an
  `#navigation-next a` link inside it. The default `index.php`
  includes this; custom templates may not.
- Infinite scroll only activates if there is a second page. If you
  only have a handful of posts it will not initialise.

### Dark mode flashing on load
- This means the synchronous scheme-init script in `header.php` is
  running after your stylesheets. Check that `wp_head()` is present
  in your `header.php` and that nothing is suppressing priority 1 hooks.

### Colour scheme not applying
- Check that `data-scheme` is set on `<html>` before the first paint.
  Open DevTools → Elements and look at the `<html>` tag.
- Confirm `ipin_dynamic_css_and_scheme()` is hooked: it runs on
  `wp_head` at priority 1 via `inc/enqueue.php`.

---

## Theme Directory Architecture

Reference for the canonical file/folder layout at each major version.
Consult this when writing migration scripts, building child themes,
or verifying that an install is complete.

---

### v4.0 — Current

```
ipin-modern/
│
├── style.css                         WordPress theme header (Version: 4.0)
├── functions.php                     Bootstrap: setup, sidebars, require /inc/
├── index.php                         Masonry grid + popular posts sort bar
├── single.php                        Single post + ad slots + share buttons
├── page.php                          Standard page (right sidebar)
├── page_full_width.php               Page template: Full Width
├── page_left_sidebar.php             Page template: Left Sidebar
├── header.php                        Skip link, navbar, flash-free scheme init
├── footer.php                        Footer, scroll-to-top button
├── comments.php                      Comments list + form
├── sidebar-left.php                  Left sidebar widget area
├── sidebar-right.php                 Right sidebar widget area
├── searchform.php                    Accessible search form
├── 404.php                           Error page
├── favicon.ico
├── screenshot.png
├── readme.txt                        User-facing documentation
├── CHANGELOG.md                      Full version history
├── UPGRADING.md                      Migration guides + architecture (this file)
│
├── inc/
│   ├── template-tags.php             ipin_option(), ipin_human_time_diff(),
│   │                                 comment callback, RSS filter,
│   │                                 ipin_lightbox_data AJAX handler
│   ├── nav-walker.php                Ipin_Nav_Walker, accessible nav filters
│   ├── post-types.php                ipin_article CPT (Sideblog)
│   ├── popular-posts.php             ipin_get_popular_posts(), pre_get_posts,
│   │                                 iPin_Popular_Posts_Widget
│   ├── ads.php                       Slot definitions, ipin_ad(),
│   │                                 ipin_grid_ad_at(), setting registration
│   ├── enqueue.php                   All wp_enqueue_style/script, dynamic CSS,
│   │                                 flash-free scheme + dark-mode init
│   ├── admin-options.php             Tabbed settings page (5 tabs), AJAX save
│   └── customizer.php                WP Customizer (logo, background)
│
├── assets/
│   ├── css/
│   │   ├── tokens.css                CSS custom properties, 5 colour schemes,
│   │   │                             dark mode overrides, --focus-ring
│   │   ├── base.css                  Reset, typography, skip link, sr-only,
│   │   │                             reduced-motion, forced-colors
│   │   ├── nav.css                   Navbar, dropdowns, hamburger,
│   │   │                             dark mode toggle, social icons
│   │   ├── masonry.css               Grid cards, hover bar, loaders,
│   │   │                             pagination, sort bar
│   │   ├── single.css                Post layout, sidebar, comments,
│   │   │                             scroll-to-top, share buttons
│   │   ├── lightbox.css              Lightbox overlay, panels, share buttons
│   │   ├── admin.css                 Admin settings page (wp-admin only)
│   │   └── editor-style.css          Block editor matching styles
│   │
│   ├── js/
│   │   ├── ipin.custom.js            Dark mode, hamburger, scroll-to-top,
│   │   │                             masonry init, infinite scroll init,
│   │   │                             copy-link share button
│   │   ├── ipin.admin.js             Tabs, swatch picker, AJAX save
│   │   ├── lightbox.js               Open/close, prev/next, keyboard nav,
│   │   │                             focus trap, AJAX data load, share buttons
│   │   ├── jquery.masonry.min.js     iPin custom masonry engine v1.0.0
│   │   ├── jquery.imagesloaded.min.js  iPin custom images-loaded detector v1.0.0
│   │   └── jquery.infinitescroll.min.js  iPin custom infinite scroll v1.0.0
│   │
│   ├── img/                          Reserved — theme images
│   └── fonts/                        Reserved — self-hosted fonts
│
├── languages/                        Translation-ready (.pot goes here)
└── template-parts/                   Reserved — future partial templates
```

**Google Site Kit / AdSense compatibility (v4.0.1):**
`wp_head()` and `wp_body_open()` are present in the correct positions.
Site Kit auto-verification and Auto Ads work without any theme changes.
Manual ad unit code (including `<script>` tags) is preserved by
`ipin_sanitize_ad_code()` in `inc/ads.php`.

**Admin tabs (v4.0):** ⚙️ General · 🎨 Appearance · 🔗 Social · 💰 Ads · 📐 Layout

**Custom post types (v4.0):** `ipin_article` (Sideblog)

**Custom DB tables (v4.0):** None

**Registered settings (v4.0):**
`ipin_frontpage_comments`, `ipin_posts_per_page`, `ipin_colour_scheme`,
`ipin_dark_mode_default`, `ipin_card_width`, `ipin_rounded_cards`,
`ipin_twitter_url`, `ipin_facebook_url`, `ipin_instagram_url`,
`ipin_rss_visible`, `ipin_show_avatars_grid`, `ipin_sidebar_position`,
`ipin_footer_text`, `ipin_ad_header`, `ipin_ad_grid_1–5`,
`ipin_ad_above_photo`, `ipin_ad_below_photo`

**Enqueue handles (v4.0):**
CSS: `ipin-google-fonts`, `ipin-font-awesome`, `ipin-tokens`, `ipin-base`,
`ipin-nav`, `ipin-single`, `ipin-lightbox`, `ipin-masonry-css` (non-singular),
`ipin-style`
JS: `ipin-masonry`, `ipin-imagesloaded`, `ipin-infinitescroll` (non-singular),
`ipin-custom`, `ipin-lightbox` (non-singular), `ipin-admin-css`,
`ipin-admin-js` (admin page only)

---

### v3.0

```
ipin-modern/
├── style.css  functions.php  *.php templates  favicon.ico  screenshot.png
├── _README.txt
├── inc/
│   ├── template-tags.php   nav-walker.php   enqueue.php
│   ├── admin-options.php   customizer.php
└── assets/
    ├── css/  tokens.css  base.css  nav.css  masonry.css  single.css
    │         admin.css  editor-style.css
    ├── js/   ipin.custom.js  ipin.admin.js
    │         jquery.masonry.min.js *    jquery.imagesloaded.min.js *
    │         jquery.infinitescroll.min.js *
    ├── img/
    └── fonts/
```
`*` — required but not bundled; user must supply.
Admin tabs: ⚙️ General · 🎨 Appearance · 🔗 Social · 📐 Layout (4 tabs)
Custom post types: none. Custom DB tables: none.

---

### v2.0

```
ipin-modern/
├── style.css  functions.php  *.php templates  favicon.ico  screenshot.png
├── css/
│   ├── tokens.css  base.css  nav.css  masonry.css  single.css
│   └── admin.css  editor-style.css
└── js/
    ├── ipin.custom.js  ipin.admin.js
    └── jquery.masonry.min.js *  jquery.imagesloaded.min.js *
        jquery.infinitescroll.min.js *
```
`*` — required but not bundled; user must supply.
Admin tabs: ⚙️ General · 🎨 Appearance · 🔗 Social · 📐 Layout (4 tabs)
All logic in `functions.php`. No `/inc/` directory.

---

### v1.0

```
ipin-modern/
├── style.css  functions.php  *.php templates  favicon.ico  screenshot.png
├── css/  (single bundled stylesheet, no modules)
└── js/   ipin.custom.js
          jquery.masonry.min.js *  jquery.imagesloaded.min.js *
          jquery.infinitescroll.min.js *
```
`*` — required but not bundled; user must supply.
No admin settings page. No `/inc/` directory.
All configuration via WP Customizer only.

---

## Future Roadmap

The items below are planned for future releases. They are not committed
to any specific version or timeline.

### v4.1 — Polish & Performance
- [ ] WebP-aware responsive image helpers (`srcset` + `sizes` auto-generation)
- [ ] Lazy-load attribute on all grid card images
- [ ] CSS container queries for card layout (remove JS resize handler)
- [ ] `prefers-reduced-motion` animation disable on masonry transitions
- [ ] Partial template extraction: `template-parts/card.php`,
      `template-parts/sort-bar.php`
- [ ] `loading="lazy"` on lightbox images
- [ ] Improved infinite scroll: Intersection Observer API replacing
      scroll event listener

### v4.2 — Sideblog & Archive Improvements
- [ ] `archive-ipin_article.php` template for the sideblog archive
- [ ] Sideblog widget displaying latest articles in the sidebar
- [ ] Category and tag archive templates that use the masonry layout
- [ ] Author archive template (`author.php`) with pin grid and stats
- [ ] Search results template (`search.php`) styled to match grid

### v4.3 — Theme Customiser Integration
- [ ] Live preview of colour scheme changes in the Customizer
- [ ] Live preview of card width slider
- [ ] Custom CSS field in the Customizer piped through the token system
- [ ] Export/import settings as JSON from the admin page

### v5.0 — Social Network Edition (Optional Add-on)
The social features below are planned as an **opt-in plugin** rather
than being bundled in the theme. This keeps the base theme lean and
ensures sites that do not need social functionality carry no overhead.

**Planned plugin: iPin Social**
- [ ] Likes system — custom DB table, AJAX toggle, cached counts
- [ ] Repins — duplicate post under current user, board assignment
- [ ] User follows — follower/following counts, follow button
- [ ] User boards (ipin_board CPT) — create, edit, assign pins
- [ ] User profile page (`page-profile.php`) — pins, boards, followers
- [ ] In-app notifications — custom DB table, notification bell in nav
- [ ] Email notifications — per-type toggles, WP Cron queue
- [ ] Frontend pin upload — file upload, URL import, video pinning
- [ ] "Pin It" bookmarklet — image picker overlay, pre-fill upload form
- [ ] User badges — Newcomer → Pinner → Curator → Influencer → Legend
- [ ] Top users leaderboard widget
- [ ] Embed view — iFrame-friendly single pin card (`embed-pin.php`)
- [ ] Facebook OAuth login
- [ ] Twitter / X OAuth 2.0 PKCE login

**Note:** All data models, DB schema, AJAX handlers, and PHP logic for
the above features were prototyped during the v4.0 development cycle
and are available for extraction into the plugin at any time.

### v5.1 — Headless / Block Editor
- [ ] Full Site Editing (FSE) template parts for block themes
- [ ] Block patterns for the masonry grid layout
- [ ] REST API endpoint for grid data (for headless / Next.js front-ends)
- [ ] WooCommerce product grid support (pin cards for products)

### Ongoing
- [ ] Translation: `.pot` file generation via WP-CLI in CI
- [ ] PHPCS WordPress Coding Standards compliance pass
- [ ] Jest unit tests for the three custom JS libraries
- [ ] Playwright end-to-end tests for lightbox, dark mode, infinite scroll
- [ ] GitHub Actions CI: lint PHP, lint JS, run tests on PR
