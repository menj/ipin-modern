/**
 * iPin Modern — Lightbox JS (no jQuery)
 * Opens on card click. Accessible: aria-modal dialog, focus trap,
 * Escape closes, Arrow keys navigate prev/next.
 *
 * All server-provided values are written with textContent or element
 * properties — nothing is string-concatenated into HTML — and the
 * video embed URL is scheme-checked before it reaches the iframe.
 */
(function () {
  'use strict';

  var data       = window.ipinData || {};
  var ajaxUrl    = data.ajaxUrl || '/wp-admin/admin-ajax.php';
  var icons      = data.icons || {};
  var overlay    = null;
  var pinIds     = [];
  var currentIdx = -1;
  var lastFocused = null;
  var el = {};   // cached refs into the overlay

  var SVG_CLOSE = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="16" height="16"><path fill="currentColor" d="M18.3 5.7a1 1 0 0 0-1.4-1.4L12 9.2 7.1 4.3a1 1 0 0 0-1.4 1.4l4.9 4.9-4.9 4.9a1 1 0 1 0 1.4 1.4l4.9-4.9 4.9 4.9a1 1 0 0 0 1.4-1.4L13.4 10.6Z" transform="translate(0 1.4)"/></svg>';
  var SVG_PREV  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="18" height="18"><path fill="currentColor" d="M15.4 5.3a1 1 0 0 0-1.4-1.4l-7 7a1 1 0 0 0 0 1.4l7 7a1 1 0 0 0 1.4-1.4L9.1 11.6Z" transform="translate(0 .4)"/></svg>';
  var SVG_NEXT  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="18" height="18"><path fill="currentColor" d="M8.6 4.9a1 1 0 0 1 1.4-1.4l7 7a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4-1.4l6.3-6.3Z" transform="translate(0 .4)"/></svg>';
  var SVG_LINK  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="13" height="13"><path fill="currentColor" d="M14 3a1 1 0 1 0 0 2h3.6l-8.3 8.3a1 1 0 0 0 1.4 1.4L19 6.4V10a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1Zm-9 4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5a1 1 0 1 0-2 0v5H5V9h5a1 1 0 1 0 0-2Z"/></svg>';
  var SVG_VIEW  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="13" height="13"><path fill="currentColor" d="M4 4h6a1 1 0 1 1 0 2H6v4a1 1 0 1 1-2 0Zm16 16h-6a1 1 0 1 1 0-2h4v-4a1 1 0 1 1 2 0Z"/></svg>';

  /* ========================================================
     BUILD DOM ON FIRST OPEN (static template — no user data)
  ======================================================== */
  function ensureDom() {
    if (overlay) return;

    overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.id = 'lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'lightbox-title');
    overlay.hidden = true;
    overlay.tabIndex = -1;

    overlay.innerHTML =
      '<div class="lightbox-dialog" id="lightbox-dialog">' +
      '  <div class="lightbox-image-panel" id="lightbox-image-panel">' +
      '    <div class="lightbox-loading" id="lightbox-loading">' +
      '      <div class="lightbox-spinner" aria-hidden="true"></div>' +
      '    </div>' +
      '    <img class="lightbox-img" id="lightbox-img" src="" alt="" decoding="async">' +
      '    <div class="lightbox-video" id="lightbox-video" hidden></div>' +
      '    <a class="lightbox-source-link" id="lightbox-source" href="#" target="_blank" rel="noopener noreferrer" hidden>' +
      SVG_LINK + ' <span id="lightbox-source-text"></span>' +
      '    </a>' +
      '  </div>' +
      '  <div class="lightbox-info-panel">' +
      '    <div class="social-actions" id="lightbox-social-actions"></div>' +
      '    <h2 class="lightbox-title" id="lightbox-title"><a id="lightbox-title-link" href="#"></a></h2>' +
      '    <div class="lightbox-meta" id="lightbox-meta"></div>' +
      '    <p class="lightbox-description" id="lightbox-desc"></p>' +
      '    <div class="lightbox-comments-preview" id="lightbox-comments" hidden>' +
      '      <div class="lightbox-comments-preview__title">Comments</div>' +
      '      <ul class="lightbox-comments-list" id="lightbox-comments-list"></ul>' +
      '      <a class="lightbox-view-all" id="lightbox-view-all" href="#">View all</a>' +
      '    </div>' +
      '  </div>' +
      '</div>' +
      // Controls sit inside the overlay so the focus trap contains
      // them (WCAG 2.1.2 — focus must not escape an open modal).
      '<button class="lightbox-close" aria-label="Close lightbox">' + SVG_CLOSE + '</button>' +
      '<button class="lightbox-nav lightbox-nav--prev" id="lb-prev" aria-label="Previous pin">' + SVG_PREV + '</button>' +
      '<button class="lightbox-nav lightbox-nav--next" id="lb-next" aria-label="Next pin">' + SVG_NEXT + '</button>';

    document.body.appendChild(overlay);

    ['lightbox-loading', 'lightbox-img', 'lightbox-video', 'lightbox-source', 'lightbox-source-text',
     'lightbox-social-actions', 'lightbox-title-link', 'lightbox-meta', 'lightbox-desc',
     'lightbox-comments', 'lightbox-comments-list', 'lightbox-view-all', 'lb-prev', 'lb-next'
    ].forEach(function (id) { el[id] = document.getElementById(id); });

    overlay.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
    el['lb-prev'].addEventListener('click', function () { navigate(-1); });
    el['lb-next'].addEventListener('click', function () { navigate(1); });

    overlay.addEventListener('click', function (e) {
      if (!e.target.closest('.lightbox-dialog') && !e.target.closest('button')) closeLightbox();
    });

    document.addEventListener('keydown', function (e) {
      if (!overlay || overlay.hidden) return;
      if (e.key === 'Escape')     closeLightbox();
      if (e.key === 'ArrowLeft')  navigate(-1);
      if (e.key === 'ArrowRight') navigate(1);
      if (e.key === 'Tab')        trapFocus(e);
    });
  }

  /* ========================================================
     OPEN / CLOSE
  ======================================================== */
  function openLightbox(postId) {
    ensureDom();
    collectPinIds();                     // never stale, even if an append event was missed
    lastFocused = document.activeElement;

    showLoading();
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    overlay.focus();

    loadPin(postId);
  }

  function stopVideo() {
    var vid = el['lightbox-video'];
    var playing = vid.querySelector('video');
    if (playing) { try { playing.pause(); } catch (e) {} }
    vid.hidden = true;
    vid.textContent = '';                // removing the node ends iframe playback too
  }

  function closeLightbox() {
    if (!overlay) return;
    overlay.hidden = true;
    document.body.style.overflow = '';
    stopVideo();
    if (lastFocused) { try { lastFocused.focus(); } catch (e) {} }
  }

  /* ========================================================
     LOAD PIN DATA VIA AJAX
  ======================================================== */
  function loadPin(postId) {
    currentIdx = pinIds.indexOf(postId);
    stopVideo();                         // prev/next must not leave audio playing
    showLoading();
    updateNavButtons();

    var body = new URLSearchParams();
    body.set('action', 'ipin_lightbox_data');
    body.set('post_id', String(postId));

    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (resp) {
        if (!resp || !resp.success) throw new Error('bad response');
        renderPin(resp.data || {});
      })
      .catch(function () {
        renderError();
      });
  }

  /* ========================================================
     RENDER
  ======================================================== */
  function safeHttpUrl(raw) {
    if (!raw) return '';                 // '' would otherwise resolve to the current page URL
    try {
      var u = new URL(raw, window.location.href);
      if (u.protocol === 'http:' || u.protocol === 'https:') return u.href;
    } catch (e) {}
    return '';
  }

  function renderPin(d) {
    var img = el['lightbox-img'];
    var vid = el['lightbox-video'];

    // Image or video. The server has already normalised and validated
    // the source; the scheme is re-checked here before it is used.
    var v   = d.video || null;
    var src = v ? safeHttpUrl(v.src) : '';
    if (src) {
      img.hidden = true;
      img.src = '';
      vid.textContent = '';
      if (v.type === 'file') {
        // Direct file (e.g. from menj.bio): native player, poster = pin image.
        var video = document.createElement('video');
        video.controls = true;
        video.playsInline = true;
        video.preload = 'metadata';
        if (d.img_url) video.poster = safeHttpUrl(d.img_url);
        video.setAttribute('aria-label', d.title || '');
        var source = document.createElement('source');
        source.src = src;
        if (v.mime) source.type = v.mime;
        video.appendChild(source);
        vid.appendChild(video);
      } else {
        var frame = document.createElement('iframe');
        frame.src = src;
        frame.title = d.title || '';
        frame.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture; encrypted-media');
        frame.setAttribute('allowfullscreen', '');
        frame.referrerPolicy = 'strict-origin-when-cross-origin';
        vid.appendChild(frame);
      }
      vid.hidden = false;
      hideLoading();                     // never leave the spinner over a video
    } else {
      vid.hidden = true;
      vid.textContent = '';
      img.hidden = false;
      img.alt = d.title || '';
      img.addEventListener('load', hideLoading);
      img.addEventListener('error', hideLoading);
      img.src = d.img_url || '';
      if (img.complete && img.src) hideLoading();
    }

    // Title
    el['lightbox-title-link'].href = safeHttpUrl(d.permalink) || '#';
    el['lightbox-title-link'].textContent = d.title || '';

    // Meta: avatar + author link + date, built as nodes
    var meta = el['lightbox-meta'];
    meta.textContent = '';
    if (d.author_avatar) {
      var av = document.createElement('img');
      av.src = safeHttpUrl(d.author_avatar);
      av.width = 22;
      av.height = 22;
      av.alt = '';
      av.style.borderRadius = '50%';
      meta.appendChild(av);
    }
    var author = document.createElement('a');
    author.href = safeHttpUrl(d.author_url) || '#';
    author.textContent = d.author_name || '';
    meta.appendChild(author);
    meta.appendChild(document.createTextNode(' · ' + (d.date || '')));

    // Description
    el['lightbox-desc'].textContent = d.description || '';

    // Source link
    var srcUrl = safeHttpUrl(d.source_url);
    if (srcUrl) {
      var host = '';
      try { host = new URL(srcUrl).hostname; } catch (e) { host = srcUrl; }
      el['lightbox-source'].hidden = false;
      el['lightbox-source'].href = srcUrl;
      el['lightbox-source-text'].textContent = host;
    } else {
      el['lightbox-source'].hidden = true;
    }

    renderSocialActions(d);

    // Comments preview
    var list = el['lightbox-comments-list'];
    list.textContent = '';
    if (d.comments && d.comments.length) {
      d.comments.forEach(function (c) {
        var li = document.createElement('li');
        li.className = 'lightbox-comment-item';
        var cav = document.createElement('img');
        cav.src = safeHttpUrl(c.avatar);
        cav.alt = '';
        cav.loading = 'lazy';
        li.appendChild(cav);
        var txt = document.createElement('div');
        txt.className = 'lightbox-comment-item__text';
        var who = document.createElement('span');
        who.className = 'lightbox-comment-item__author';
        who.textContent = c.author || '';
        txt.appendChild(who);
        txt.appendChild(document.createTextNode(c.text || ''));
        li.appendChild(txt);
        list.appendChild(li);
      });
      el['lightbox-view-all'].href = (safeHttpUrl(d.permalink) || '#') + '#comments';
      el['lightbox-comments'].hidden = false;
    } else {
      el['lightbox-comments'].hidden = true;
    }

    updateNavButtons();
  }

  function renderError() {
    hideLoading();
    el['lightbox-img'].hidden = true;
    el['lightbox-video'].hidden = true;
    el['lightbox-title-link'].textContent = 'This pin could not be loaded.';
    el['lightbox-title-link'].removeAttribute('href');
    el['lightbox-meta'].textContent = '';
    el['lightbox-desc'].textContent = 'Please try again, or open the post directly.';
    el['lightbox-social-actions'].textContent = '';
    el['lightbox-comments'].hidden = true;
    el['lightbox-source'].hidden = true;
    updateNavButtons();
  }

  /* ========================================================
     SHARE ACTIONS
  ======================================================== */
  function shareLink(className, label, href, iconSvg, extraText) {
    var a = document.createElement('a');
    a.className = 'btn-social ' + className;
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.setAttribute('aria-label', label);
    if (iconSvg) a.innerHTML = iconSvg;   // theme-bundled icon markup, not user data
    if (extraText) a.appendChild(document.createTextNode(' ' + extraText));
    return a;
  }

  function renderSocialActions(d) {
    var sa = el['lightbox-social-actions'];
    sa.textContent = '';
    var permalink = safeHttpUrl(d.permalink);
    if (!permalink) return;
    var title = d.title || '';

    sa.appendChild(shareLink(
      'btn-share-pinterest',
      'Save to Pinterest (opens in new tab)',
      'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(permalink) + '&description=' + encodeURIComponent(title),
      icons.pinterest || ''
    ));
    sa.appendChild(shareLink(
      'btn-share-twitter',
      'Share on X / Twitter (opens in new tab)',
      'https://twitter.com/intent/tweet?url=' + encodeURIComponent(permalink) + '&text=' + encodeURIComponent(title),
      icons.x || ''
    ));
    sa.appendChild(shareLink(
      'btn-share-facebook',
      'Share on Facebook (opens in new tab)',
      'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(permalink),
      icons.facebook || ''
    ));

    var view = document.createElement('a');
    view.className = 'btn-social btn-view-post';
    view.href = permalink;
    view.innerHTML = SVG_VIEW;
    view.appendChild(document.createTextNode(' View'));
    sa.appendChild(view);
  }

  /* ========================================================
     NAVIGATION
  ======================================================== */
  function navigate(dir) {
    if (!pinIds.length) return;
    var idx = currentIdx + dir;
    if (idx < 0 || idx >= pinIds.length) return;
    currentIdx = idx;
    loadPin(pinIds[idx]);
  }

  function updateNavButtons() {
    var prev = el['lb-prev'];
    var next = el['lb-next'];
    var wasFocused = document.activeElement;
    prev.disabled = currentIdx <= 0;
    next.disabled = currentIdx >= pinIds.length - 1;
    // A button disabled while focused would drop keyboard focus out
    // of the modal (WCAG 2.1.2) — hand it back to the overlay.
    if (wasFocused && wasFocused.disabled) overlay.focus();
  }

  /* ========================================================
     LOADING STATE
  ======================================================== */
  function showLoading() {
    el['lightbox-loading'].hidden = false;
    el['lightbox-img'].src = '';
  }

  function hideLoading() {
    el['lightbox-loading'].hidden = true;
  }

  /* ========================================================
     FOCUS TRAP — recomputed per keypress, disabled controls
     excluded, focus recovered when it has left the overlay.
  ======================================================== */
  function trapFocus(e) {
    var sel = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    var foc = Array.prototype.filter.call(overlay.querySelectorAll(sel), function (n) {
      return !n.hidden && !n.closest('[hidden]') && !n.disabled;
    });
    if (!foc.length) { e.preventDefault(); overlay.focus(); return; }
    var first = foc[0];
    var last  = foc[foc.length - 1];
    var active = document.activeElement;
    if (!overlay.contains(active)) { e.preventDefault(); first.focus(); return; }
    if (e.shiftKey && (active === first || active === overlay)) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && active === last) { e.preventDefault(); first.focus(); }
  }

  /* ========================================================
     INIT: collect pin IDs + bind card clicks
  ======================================================== */
  function collectPinIds() {
    pinIds = Array.prototype.map.call(
      document.querySelectorAll('#masonry .thumb[data-post-id]'),
      function (n) { return parseInt(n.getAttribute('data-post-id'), 10); }
    ).filter(Boolean);
  }

  collectPinIds();
  document.addEventListener('ipin:infiniteScrollLoaded', collectPinIds);

  // Card click — open lightbox
  document.addEventListener('click', function (e) {
    var wrapLink = e.target.closest('.thumb-img-wrap');
    if (!wrapLink) return;
    var card = wrapLink.closest('[data-post-id]');
    if (!card) return;
    e.preventDefault();
    openLightbox(parseInt(card.getAttribute('data-post-id'), 10));
  });

  // Shift+Enter on a card title link opens the lightbox instead
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || !e.shiftKey) return;
    var link = e.target.closest('.thumb .thumbtitle a');
    if (!link) return;
    var card = link.closest('[data-post-id]');
    if (!card) return;
    e.preventDefault();
    openLightbox(parseInt(card.getAttribute('data-post-id'), 10));
  });
})();
