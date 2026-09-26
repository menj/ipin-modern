# Changelog

All notable changes to **iPin Modern** are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

## [5.1.0] - 2026-09-26 - The 4.5 features, restored

The 5.0 rebuild started from 4.1.4, so the work of the 4.1.5 to 4.5.0 line,
which never reached the repository, was missing from it. This release ports
that work onto the 5.0 code. Much of the 4.x work was front-end repair on
code that 5.0 replaced; that part is already covered and is not repeated
here. Everything below is a feature that 4.5 had and 5.0 lacked, rebuilt to
5.0's conventions (Settings API schema, bundled SVG icons, OKLCH tokens, no
jQuery). Settings saved under 4.5 use the same option names and carry over.
The 4.5.0 archive itself cannot activate: its `functions.php` begins with a
byte-order mark, which PHP rejects before `declare( strict_types = 1 )`.
Read [UPGRADING.md](UPGRADING.md) if you are coming from 4.5.

### Added
- Hidden tags (`inc/hidden-tags.php`) and a Visibility tab to pick them, with
  a filter box. Posts carrying a hidden tag leave the grid, archives, search,
  feeds, the core sitemap and the previous/next links on single posts; the
  tags leave tag lists, tag clouds, `article:tag` and share hashtags. Each
  post still opens at its own address, and the tag's own archive still lists
  them with `noindex`. Custom queries can opt in through the
  `ipin_query_args` filter.
- Markdown (`inc/markdown.php`): a "Write in Markdown" switch per post and
  article, cached HTML per post, and `assets/css/markdown.css`. Optional
  Markdown in comments (Settings → Layout) for bold, italic, strikethrough,
  code and links, with single newlines kept.
- `markdown.css` is 4.4.58's Markdown stylesheet, ported: heading scale with
  a hover `#` on linkable headings, paragraph rhythm, inline code chips, dark
  code panels with a language badge, tinted blockquotes (nested ones
  lighter), rules, list spacing, task lists, bordered tables with uppercase
  headers, row hover and scrolling, and image placement. Every colour now
  comes from `tokens.css`: 4.4.58's file named tokens that never existed
  (`--accent`, `--muted`, `--surface-1` and five more), so most of its
  colours never applied. Pairings are checked to AA in all ten schemes, both
  modes. One colour changed: the language badge is #A6ADC8 (7.37:1), since
  4.4.58's 40% white measured 3.75:1. Task-list boxes are drawn in CSS,
  because WordPress strips the checkbox inputs 4.4.58 relied on.
- The parser is cebe/markdown 1.2 (MIT, GitHub flavour), vendored in
  `inc/vendor/cebe-markdown/` and loaded only when a page renders Markdown.
  `Ipin_Markdown` (`inc/class-ipin-markdown.php`) extends it with heading
  ids, task lists, scrolling table wrappers and a sanitised code-language
  class, and drops links and images whose scheme WordPress does not allow.
  It replaces 4.x's 350-line built-in parser and the optional Parsedown
  drop-in. Output still passes through `wp_kses_post()`. HTML cached by the
  old parser is re-rendered once, on each post's next view.
- Five more colour schemes, Rose Gold, Aurora, Dusk, Copper and Arctic, with
  dark modes. Their decorative colours and surfaces are 4.5's; accents and
  fills are re-solved in OKLCH to the same WCAG 2.2 AA targets as the other
  five, with the ratios in `tokens.css`.
- Auto-rotate (Settings → Appearance): each visitor gets a random scheme,
  kept in the browser for 1 hour to 1 week. The browser-bar colour follows it.
- Card settings: gap (4 to 48 px), a fixed image height (0 keeps each
  image's proportions), shadow depth (none, subtle, pronounced) and hover zoom.
- 46 social and identity profiles, up from 3: every icon in the Minimalist
  Social Icons Pack 2.8 (45) plus Open Library's 4.x drawing. The Social tab
  groups them in six cards (social networks; messaging and community; video,
  music and creative; writing and publishing; identifiers and libraries;
  work, code and games), each field with its icon. WorldCat uses OCLC's mark
  and GamingTribe the GTribe mark. All profiles join the `sameAs` links in
  structured data, and each link carries `rel="me"`.
- The top bar shows the first few profiles (Settings → Social → "Icons
  before More", default 5) and folds the rest into a "More profiles" menu: a
  native `<details>`, so it opens without JavaScript, with Escape and
  click-outside to close. On phones it opens in place inside the menu panel.
- The pack's LinkedIn and Scribd icons come from Font Awesome Free (CC BY
  4.0); their SVG files keep Font Awesome's licence comment, and the readme
  carries the credit.
- Share bar: WhatsApp, Telegram, Threads, Mastodon, Email, and a "Share via…"
  button for the device's share sheet (shown only where the Web Share API
  exists). X gets the post's tags as `&hashtags=`; Threads and Mastodon get
  them inline as `#CamelCase`. The lightbox adds Mastodon and hashtags, sends
  the image to Pinterest, and its Pinterest button can be switched off.
- Settings → Search, a new tab for structured data and sameAs:
  - A switch for all JSON-LD output.
  - Site identity: the site represents a person (a chosen user, by default
    the first administrator) or an organization (a name, with the Custom
    Logo or Site Icon as logo). It is one node, `#identity`, that carries
    the sameAs links; the WebSite and every Article name it as publisher,
    and when the site is a person, that person's posts name it as author.
  - sameAs: the Social tab's profiles and Mastodon handle (switchable), a
    list of further URLs, and the person's Website field. Only http(s)
    URLs are kept. A preview lists exactly what is output.
  - A notice when an SEO plugin is active and the theme is standing down.
- Visible breadcrumbs above post and article titles (Home, category or
  Articles, title), from the same list as the BreadcrumbList markup.
- `inc/opengraph.php`: Open Graph and Twitter Card tags on singular views and
  the home page. The description comes from the same helper as the
  description meta tag. Stands down with the rest of `seo.php` when an SEO
  plugin is active.
- `inc/pinterest.php`: Rich Pin meta and `pinterest:media` on singular views,
  and the Pinterest Tag when a Tag ID is set under Settings → Layout. The Tag
  is the one script the theme loads from another server, and only on request.
- `inc/performance.php`: preconnect hints for Gravatar and Pinterest, an LCP
  preload with `srcset` for the featured image, no lazy-loading on that image,
  head clean-up (emoji, generator, RSD, WLW, shortlink, REST and oEmbed
  discovery links), Heartbeat off on the front end, and no self-pingbacks.
- `inc/pin-source.php`: the "Pin source" editor box for `_ipin_source_url`.
  The lightbox already showed the link; nothing could set it.
- The `[ipin_sideblog]` shortcode (`count`, `title`) and the "Sideblog: Latest
  Articles" block pattern.
- A switch for previous/next links on single posts (Settings → Layout).
- The number of results under the search heading.

### Changed
- Structured data follows Google Search Central's rules for the features it
  supports. sameAs moved from WebSite to the site identity (Organization or
  Person), where Google's Organization and Profile page guidelines put it. Authors other than the site's own person get a plain Person
  with only their own Website as sameAs; 5.0 gave every author the site's
  profiles. ProfilePage now carries the author page URL and profile image.
  VideoObject is emitted only when the pin has a featured image, since
  Google requires `thumbnailUrl`.
- Sorted grid views (`?popular=`) are `noindex, follow`: they repeat the
  grid in another order, the kind of search-result-like duplicate Google's
  starter guide asks to keep out of the index.
- 4.5 served articles at `/blog/{slug}/` and `/blog/`. Those addresses now
  redirect (301) to `/article/{slug}/` and `/articles/`. Only requests that
  would otherwise 404 are redirected, so a page or category at `/blog/` wins.
- Grid comment previews and lightbox comments show Markdown as plain text
  when comment Markdown is on.
- The header builds its social links from one list, `ipin_social_profiles()`.
- `ipin_theme_colors()` reads from the new `ipin_theme_color_map()`.
- Range sliders on the settings page share one handler.

### Not carried over from 4.5
- The ad slot manager and the sidebars, both removed on purpose in 5.0.
- 4.5's `schema.php` and `seo-meta.php`: `seo.php` covers the same ground.
- 4.5's article templates: `single.php` and `index.php` render articles.
- `ipin_card_show_avatar`, which duplicated `ipin_show_avatars_grid`.
- Moving jQuery to the footer (5.0 loads no jQuery) and the AVIF/WebP upload
  transforms (they change the image files WordPress stores).

### Fixed
- Comment Markdown links came out empty whenever core's `make_clickable`
  ran first, which it did (4.5 bug). The filter now runs before it.
- Markdown links with a blocked scheme (`javascript:` and the like) now stay
  as typed text; they used to become an empty link.
- Comment Markdown output is limited to the comment tag allow-list, and its
  links get `rel="nofollow ugc"`.
- The share hashtag helper read `ipin_hidden_tags`, an option that never
  existed; it now reads the hidden-tag list itself (4.5 bug).
- 4.5's Pinterest meta printed `og:image:type` as `image/jpeg` for every image.
- 4.5.0 enqueued `markdown.css` but had lost the file (4.4.58 had it).
- 4.4.58's code-language badge never showed: it read `data-lang` from a
  `<pre>` that never carried one. `Ipin_Markdown` now sets it.
- 4.4.58 numbered bulleted lists nested inside numbered ones.
- The Sideblog shortcode linked to `/articles/` while the archive lived at
  `/blog/` (4.5 bug); it now uses the archive link.
- Single posts: the share bar ran to the card's edge, and previous/next links
  showed list bullets.

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
- The Sideblog is built into the theme and works on activation, with no plugin
  to install. Articles (`ipin_article`) get an archive at `/articles/` with an
  "Articles" heading, pages at `/article/{slug}/`, their own editor labels and
  revisions. With no menu assigned, the top bar lists Articles once one is
  published. Breadcrumb schema runs Home → Articles → article.
- Cards are CSS size containers, so their type follows the card width setting.
- Cards ease in on a CSS scroll-driven timeline (off under reduced motion).
- Cross-document View Transitions: a card's image morphs into the post's
  featured image in browsers that support it.
- A 404 page with some cheek: "4 0 4" with a loose, wobbling pin for the
  zero, one of six headlines picked at random, and three ways out (back to
  the board, a random pin, search). Add or replace the lines with the
  `ipin_404_quips` filter. Its styles live in `404.css`, and the 404 view no
  longer loads the grid and lightbox CSS or JavaScript.
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
- File layout. `index.php` loads its hero, sort bar and cards from
  `template-parts/`, and `single.php` its share bar. Scripts are named after
  their stylesheets: `theme.js`, `grid.js` (with `grid.css`, formerly
  `masonry.css`), `lightbox.js`, `admin.js`. Handles follow: `ipin-theme`,
  `ipin-grid`, `ipin-admin`. Favicons moved to `assets/img/`. The walker is
  `inc/class-ipin-nav-walker.php`, and RSS and REST code moved out of
  `template-tags.php` into `inc/feed.php` and `inc/rest-api.php`. The HTML
  every page renders is unchanged.

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
- JSON-LD names and headlines carried HTML entities (`&#038;` for `&`). They
  are plain text now, and `<`, `>` and `&` are written as `\u003C`-style
  escapes, so no title can end the script element or open a comment in it.
- Cards printed a stray "in", and single views an empty "Categories:", for
  entries without categories.
- The header's social links had `role="listitem"`, which replaced their link
  role for screen readers, and the dark-mode button sat inside that list. They
  are now a real list, with the button after it.

### Security
- The lightbox endpoint returned data for password-protected and
  non-public posts. The REST route refuses both.
- Video URLs were concatenated into iframe HTML. The lightbox now builds all
  markup with DOM properties, and URLs are scheme-checked on both ends.
- The featured-pin panel showed a sticky post's title, image and link after
  the post was made private, draft or password-protected. Only published
  posts without a password qualify now.

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
