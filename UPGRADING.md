# Upgrading iPin Modern

Migration guides for each major version jump, a troubleshooting list, and the roadmap.

## Upgrading to 5.0.0 (from any 4.x)

5.0 rebuilds the front end. Most sites update in place, but a few things were removed on purpose. Check this list first, and back up the site before you update.

### Before you update

1. **Sidebars are gone.** Widgets you placed in the Right Sidebar or Left Sidebar stop showing. WordPress keeps them under Appearance → Widgets as inactive, so you can copy anything you still need.
2. **Two page templates are gone:** `page_left_sidebar.php` and `page_full_width.php`. Pages that used them fall back to the default page layout, which is now full width anyway. Nothing to do unless a child theme references them.
3. **The Ads tab is gone.** Saved ad code stays in the database (`ipin_ad_*` options) but is never output. If you ran AdSense Auto Ads through Google Site Kit, that still works, because Site Kit injects its own script.
4. **The Popular Posts widget is gone.** With no widget areas left, it had nowhere to go. The sort bar above the grid does the same ranking.
5. **The text domain changed** from `ipin` to `ipin-modern`. If you made your own translation, rename the files (`ipin-fr_FR.mo` becomes `ipin-modern-fr_FR.mo`) or regenerate them from `languages/ipin-modern.pot`.
6. **jQuery isn't loaded by the theme any more.** A child theme or custom script that relied on the theme pulling in jQuery must enqueue it itself (`wp_enqueue_script( 'jquery' )`). Code that called `$.fn.masonry`, `imagesLoaded` or `infinitescroll` needs updating; see "Child themes" below.

### After you update

1. Clear any page cache and CDN cache. Asset URLs carry the new version number, so browsers fetch fresh files, but cached HTML can still point at the old ones.
2. Open Appearance → iPin Settings. Check the new Layout tab (homepage hero) and the new Social fields (sameAs list, Mastodon handle).
3. The Sideblog needs nothing from you: the theme registers it, as it always has. If you installed the iPin Sideblog plugin from an earlier 5.0 build, deactivate and delete it. The theme notices the plugin and says so on the Plugins screen; your articles stay where they are.
4. Optional: regenerate thumbnails (for example `wp media regenerate --yes`) so older uploads get the new 800px `ipin-card` size. Cards work without it; they just use the nearest existing size.

### Child themes

- Grid markup is unchanged apart from these: the card's hover chips are `<span class="btn">` instead of links, video pins add `.thumb--video` and a `.thumb-play` badge, and the comments wrapper in `single.php` and `page.php` lost its duplicate `id="comments"` (the id now lives only on the section in `comments.php`).
- The grid engine lives in `assets/js/grid.js`. After it appends a page of pins it fires `ipin:infiniteScrollLoaded` on `document`; listen for that instead of the old jQuery callbacks.
- Files and enqueue handles were renamed so each script matches its stylesheet. If a child theme dequeues, deregisters or depends on one of these, update the name:

  | 4.x | 5.0 |
  |---|---|
  | `assets/js/ipin.custom.js`, handle `ipin-custom` | `assets/js/theme.js`, handle `ipin-theme` |
  | `assets/js/ipin.admin.js`, handle `ipin-admin-js` | `assets/js/admin.js`, handle `ipin-admin` |
  | `assets/css/masonry.css`, handle `ipin-masonry-css` | `assets/css/grid.css`, handle `ipin-grid` |
  | handle `ipin-admin-css` | handle `ipin-admin` |
  | `favicon.svg`, `favicon.ico` in the theme root | `assets/img/` |
  | `inc/nav-walker.php` | `inc/class-ipin-nav-walker.php` (class name unchanged) |

- `index.php` now loads its pieces from `template-parts/`: `home-hero.php`, `sort-bar.php` and `card.php` (one pin); `single.php` loads `share-bar.php`. To change one, copy just that file into the same path in your child theme. `card.php` receives `comments_per_card` and `previews` in `$args`.
- Colour tokens are now in OKLCH, and gradients, borders and shadows are derived from a few source colours. Overriding `--clr-accent-*`, `--clr-fill-*` or `--clr-vivid-*` is enough; you don't need to repeat the gradients. Use `--clr-fill-*` for solid buttons with white labels, since `--clr-accent-*` turns into a light text colour in dark mode.
- `ipin_dynamic_css_and_scheme()` no longer prints a `<style>` block. Card width and corner radius arrive through `wp_add_inline_style()` on the `ipin-tokens` handle.
- Lightbox data comes from `GET /wp-json/ipin/v1/pin/{id}`. The `ipin_lightbox_data` admin-ajax action is gone.

---

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

See the Installation section in `readme.txt`. Short version:

1. Upload `ipin-modern/` to `wp-content/themes/` (or upload the zip under Appearance → Themes → Add New).
2. Activate it under Appearance → Themes.
3. Configure it under Appearance → iPin Settings.

---

## Known issues

### The grid shows as plain columns and never becomes a masonry layout
- The CSS-columns layout is the no-JavaScript fallback. If you see it with JavaScript on, `grid.js` isn't running. Check the browser console for errors and the Network tab for `assets/js/grid.js`.
- Optimisation plugins that combine or defer scripts can break the load order. `grid.js` depends on `theme.js`; exclude both from combining if in doubt.

### Infinite scroll doesn't load more pins
- The grid engine follows the `#navigation-next a` link. The default `index.php` prints it; a custom template has to as well.
- It only runs when there's a second page. Check Appearance → iPin Settings → General → Posts per page against your post count.

### A video pin won't play
- The Video pin box in the editor says whether a link is playable. Use the direct file link, the one ending in .mp4, .webm, .m4v, .mov or .ogv. A link to the page that shows the video won't play.
- Sites that forbid framing (they send `X-Frame-Options: SAMEORIGIN`) still work with a file link, because the file plays in a native `<video>` element.
- A file that plays in one browser but not another is usually a codec issue. H.264 in .mp4 plays nearly everywhere; .webm is a good second file.

### `/articles/` or an article page shows "not found"
- Go to Settings → Permalinks and click Save Changes. That rebuilds the rewrite rules. The theme does this when it is activated, but a caching or redirect plugin can hold on to old rules.

### Articles are missing after switching themes
- The Sideblog belongs to the theme, so another theme doesn't know about it. Nothing is deleted: switch back and the articles return, with their links.

### The lightbox says "This pin could not be loaded"
- Password-protected posts are refused by design; open the post itself.
- A security plugin may be blocking the REST API for visitors. Allow `GET /wp-json/ipin/v1/pin/*`.

### Google Site Kit: site not verified
- Confirm `wp_head()` is present in your `header.php`. It is in the theme, but a custom child theme may have removed it.
- Site Kit verification adds a `<meta>` tag through `wp_head`. If verification fails, check that no caching plugin is stripping meta tags from the `<head>`.

### Dark mode flashes on load
- The pre-paint script runs on `wp_head` at priority 1. Check that `wp_head()` is in your `header.php` and that nothing unhooks `ipin_dynamic_css_and_scheme()`.

### Colour scheme not applying
- Check that `data-scheme` is set on `<html>` before the first paint (DevTools → Elements).
- If you added your own scheme, it also needs an entry in `ipin_colour_schemes()`, or the settings page won't save it.

---

## Theme Directory Architecture

Reference for the canonical file/folder layout at each major version.
Consult this when writing migration scripts, building child themes,
or verifying that an install is complete.

---

### v5.0 (current)

```
ipin-modern/
├── style.css                    Theme header, and a map of the stylesheets and scripts
├── functions.php                Module loader, theme setup, grid page size
├── screenshot.png               1200 × 900
├── readme.txt  CHANGELOG.md  UPGRADING.md
│
├── header.php  footer.php       Top bar and footer, on every view
├── index.php                    Grid views: home, archives, search
├── single.php  page.php         Posts (and articles), pages; full width
├── 404.php                      Not-found page
├── comments.php  searchform.php
│
├── template-parts/
│   ├── home-hero.php            Hero heading, lede and featured-pin panel
│   ├── sort-bar.php             Latest / 7 days / this month / all time
│   ├── card.php                 One pin in the grid (also what infinite scroll loads)
│   └── share-bar.php            Share buttons on single posts
│
├── inc/
│   ├── template-tags.php        Helpers the templates call, comment callback, 404 lines
│   ├── class-ipin-nav-walker.php  Menu walker with disclosure buttons
│   ├── enqueue.php              Assets, inline tokens, scheme init, theme-color, favicon
│   ├── post-types.php           Sideblog post type (ipin_article)
│   ├── popular-posts.php        Sort bar ordering (?popular=) and links
│   ├── video.php                Video pins: parser, player, editor box
│   ├── seo.php                  Meta description, JSON-LD, sameAs, Mastodon tags
│   ├── feed.php                 RSS: pin images and enclosures
│   ├── rest-api.php             Lightbox data: GET /wp-json/ipin/v1/pin/{id}
│   ├── admin-options.php        Settings schema and the tabbed settings page
│   └── customizer.php           Customizer notice, Colors section handling
│
├── assets/
│   ├── css/                     fonts, tokens, base, nav, grid, lightbox, single, 404, admin, editor-style
│   ├── js/                      theme.js, grid.js, lightbox.js, admin.js
│   ├── fonts/                   EB Garamond, Sabon Next LT, Special Elite (WOFF2)
│   └── img/                     favicon.svg, favicon.ico (used when no Site Icon is set)
│       └── social/              x, facebook, instagram, pinterest (SVG)
│
└── languages/ipin-modern.pot
```

**Enqueue handles (v5.0):**
CSS: `ipin-fonts`, `ipin-tokens`, `ipin-base`, `ipin-nav`, then `ipin-single`
(posts and pages), `ipin-404`, or `ipin-grid` + `ipin-lightbox` (grid views);
`ipin-style`. Admin: `ipin-tokens`, `ipin-admin`.
JS: `ipin-theme` (every view), `ipin-grid` + `ipin-lightbox` (grid views).
Admin: `ipin-admin`.

No jQuery. No bundled third-party libraries. No sidebars or widget areas.

---

### v4.0

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

These have no dates yet. 5.0 delivered the old 4.1 list (responsive images, lazy loading, container queries, reduced-motion support, IntersectionObserver scrolling) and the `.pot` file.

### Templates
- [ ] Extract `template-parts/card.php` and `template-parts/sort-bar.php` from `index.php`
- [ ] `archive-ipin_article.php` for the Sideblog archive
- [ ] Author archive with a pin grid and simple stats

### Settings
- [ ] Live Customizer preview for colour scheme and card width
- [ ] Export and import settings as JSON

### iPin Social (optional plugin)
The social features planned during 4.0 (likes, repins, follows, boards, profiles, notifications, front-end pin upload, a "Pin It" bookmarklet, OAuth login) belong in a separate plugin, so sites that don't want them carry none of the weight.

### Block themes
- [ ] Block patterns for the hero and the grid
- [ ] A block-theme (FSE) edition

### Tooling
- [ ] PHPCS (WordPress Coding Standards) and JS linting in CI
- [ ] Playwright tests for the grid, lightbox, dark mode and menus
- [ ] Regenerate the `.pot` file in CI
