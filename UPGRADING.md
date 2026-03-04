# Upgrading iPin Modern

Migration guides for each major version jump, plus the future roadmap.

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
