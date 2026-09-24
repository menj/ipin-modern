/**
 * iPin Modern — Grid Engine (no jQuery)
 *
 * Masonry layout + infinite scroll in ~180 lines of vanilla JS,
 * replacing the bundled jquery.masonry / jquery.imagesloaded /
 * jquery.infinitescroll stack.
 *
 * - Card images carry width/height attributes, so item heights are
 *   known before any pixel downloads: layout runs immediately and
 *   native loading="lazy" keeps working (no proxy Image() fetches).
 * - Column count is re-derived from the wrapper on every layout,
 *   so the grid reflows on resize and device rotation.
 * - Next pages are fetched from the #navigation-next link on an
 *   IntersectionObserver sentinel; appended cards keep their markup
 *   verbatim and 'ipin:infiniteScrollLoaded' fires for the lightbox.
 */
(function () {
  'use strict';

  var grid = document.getElementById('masonry');
  if (!grid) return;

  var wrap = grid.parentElement;
  var data = window.ipinData || {};

  /* ========================================================
     MASONRY LAYOUT
  ======================================================== */
  function items() {
    return Array.prototype.filter.call(grid.children, function (el) {
      return el.classList.contains('thumb');
    });
  }

  function layout() {
    var list = items();
    if (!list.length) return;

    grid.classList.add('masonry-js');
    grid.style.width = '';              // release the previous fitWidth so we re-measure honestly

    var gap  = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--card-gap')) || 14;
    var colW = list[0].offsetWidth;     // reflects --card-width and the mobile media-query override
    if (!colW) return;

    var ws     = getComputedStyle(wrap);
    var avail  = wrap.clientWidth - parseFloat(ws.paddingLeft) - parseFloat(ws.paddingRight);
    var cols   = Math.max(1, Math.floor((avail + gap) / (colW + gap)));
    var used   = Math.min(cols, list.length);
    grid.style.width = (used * (colW + gap) - gap) + 'px';

    var heights = new Array(cols).fill(0);
    list.forEach(function (el) {
      var col = heights.indexOf(Math.min.apply(null, heights));
      el.style.left = (col * (colW + gap)) + 'px';
      el.style.top  = heights[col] + 'px';
      heights[col] += el.offsetHeight + gap;
    });
    grid.style.height = (Math.max.apply(null, heights) - gap) + 'px';
    grid.setAttribute('aria-busy', 'false');
  }

  var relayoutQueued = false;
  function queueLayout() {
    if (relayoutQueued) return;
    relayoutQueued = true;
    window.requestAnimationFrame(function () {
      relayoutQueued = false;
      layout();
    });
  }

  layout();

  // Re-run when the wrapper resizes (viewport, rotation, scrollbar).
  if ('ResizeObserver' in window) {
    var ro = new ResizeObserver(queueLayout);
    ro.observe(wrap);
  } else {
    window.addEventListener('resize', queueLayout);
  }

  // Fonts and late image metadata can change card heights.
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(queueLayout);
  }
  window.addEventListener('load', queueLayout);
  grid.addEventListener('load', queueLayout, true); // capture: img load doesn't bubble

  // The pre-JS fallback loader is obsolete the moment we lay out.
  var bootLoader = document.getElementById('ajax-loader-masonry');
  if (bootLoader) bootLoader.remove();

  /* ========================================================
     INFINITE SCROLL
  ======================================================== */
  var nav      = document.getElementById('navigation');
  var nextA    = document.querySelector('#navigation-next a');
  var nextUrl  = nextA ? nextA.href : null;
  var live     = document.getElementById('ipin-live-region');
  var loading  = false;

  if (!nextUrl) return;
  if (nav) nav.hidden = true;           // pagination stays for no-JS visitors only

  var status = document.createElement('div');
  status.className = 'grid-status';
  status.hidden = true;
  wrap.appendChild(status);

  var sentinel = document.createElement('div');
  sentinel.className = 'grid-sentinel';
  sentinel.setAttribute('aria-hidden', 'true');
  wrap.appendChild(sentinel);

  function announce(text) {
    status.textContent = text;
    status.hidden = !text;
    if (live) live.textContent = text;
  }

  function finish() {
    io.disconnect();
    sentinel.remove();
    announce(data.allLoaded || 'All items loaded');
    setTimeout(function () { status.hidden = true; }, 2500);
  }

  function fetchNext() {
    if (loading || !nextUrl) return;
    loading = true;
    announce(data.loadingText || 'Loading more pins…');

    fetch(nextUrl, { credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(function (html) {
        var doc  = new DOMParser().parseFromString(html, 'text/html');
        var news = doc.querySelectorAll('#masonry .thumb');
        var frag = document.createDocumentFragment();
        news.forEach(function (n) { frag.appendChild(document.adoptNode(n)); });
        grid.appendChild(frag);

        var a  = doc.querySelector('#navigation-next a');
        nextUrl = a ? a.href : null;

        layout();
        document.dispatchEvent(new CustomEvent('ipin:infiniteScrollLoaded'));

        loading = false;
        if (nextUrl) {
          announce('');
        } else {
          finish();
        }
      })
      .catch(function () {
        // Network hiccup: stop announcing, allow the next intersection to retry.
        loading = false;
        announce('');
      });
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) fetchNext();
    });
  }, { rootMargin: '600px 0px' });

  io.observe(sentinel);
})();
