/**
 * iPin Modern — Lightbox JS
 * Opens on card click/enter. Accessible: aria-modal dialog,
 * focus trap, Escape closes, Arrow keys navigate prev/next.
 */
(function ($) {
  'use strict';

  var ajaxUrl    = (window.ipinData || {}).ajaxUrl || '/wp-admin/admin-ajax.php';
  var nonce      = '';   // no social actions in design-only build
  var $overlay   = null;
  var $dialog    = null;
  var pinIds     = [];   // array of post IDs on current page
  var currentIdx = -1;
  var lastFocused = null;

  /* ========================================================
     BUILD DOM ON FIRST OPEN
  ======================================================== */
  function ensureDom() {
    if ($overlay) return;

    $overlay = $([
      '<div class="lightbox-overlay" id="lightbox-overlay" role="dialog"',
      '     aria-modal="true" aria-labelledby="lightbox-title"',
      '     hidden tabindex="-1">',
      '  <div class="lightbox-dialog" id="lightbox-dialog">',

      '    <!-- Image panel -->',
      '    <div class="lightbox-image-panel" id="lightbox-image-panel">',
      '      <div class="lightbox-loading" id="lightbox-loading">',
      '        <div class="lightbox-spinner" aria-hidden="true"></div>',
      '      </div>',
      '      <img class="lightbox-img" id="lightbox-img" src="" alt="" decoding="async">',
      '      <div class="lightbox-video" id="lightbox-video" hidden></div>',
      '      <a class="lightbox-source-link" id="lightbox-source" href="#" target="_blank" rel="noopener noreferrer" hidden>',
      '        <i class="fa fa-external-link-alt" aria-hidden="true"></i> <span id="lightbox-source-text"></span>',
      '      </a>',
      '    </div>',

      '    <!-- Info panel -->',
      '    <div class="lightbox-info-panel">',
      '      <div class="social-actions" id="lightbox-social-actions"></div>',
      '      <h2 class="lightbox-title" id="lightbox-title"><a id="lightbox-title-link" href="#"></a></h2>',
      '      <div class="lightbox-meta" id="lightbox-meta"></div>',
      '      <p class="lightbox-description" id="lightbox-desc"></p>',
      '      <div class="lightbox-comments-preview" id="lightbox-comments" hidden>',
      '        <div class="lightbox-comments-preview__title">Comments</div>',
      '        <ul class="lightbox-comments-list" id="lightbox-comments-list"></ul>',
      '        <a class="lightbox-view-all" id="lightbox-view-all" href="#">View all</a>',
      '      </div>',
      '    </div>',

      '  </div><!-- /.lightbox-dialog -->',
      '</div>',
    ].join(''))[0];

    // Close button
    var $close = $('<button class="lightbox-close" aria-label="Close lightbox"><i class="fa fa-times" aria-hidden="true"></i></button>');
    // Prev/Next
    var $prev = $('<button class="lightbox-nav lightbox-nav--prev" id="lb-prev" aria-label="Previous pin"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>');
    var $next = $('<button class="lightbox-nav lightbox-nav--next" id="lb-next" aria-label="Next pin"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>');

    $('body').append($overlay).append($close).append($prev).append($next);

    $overlay = $('#lightbox-overlay');
    $dialog  = $('#lightbox-dialog');

    // Event binding
    $close.on('click', closeLightbox);
    $prev.on('click', function () { navigate(-1); });
    $next.on('click', function () { navigate(+1); });

    $overlay.on('click', function (e) {
      if (!$(e.target).closest('.lightbox-dialog').length) closeLightbox();
    });

    $(document).on('keydown.lightbox', function (e) {
      if ($overlay.attr('hidden') !== undefined && $overlay[0].hidden) return;
      if (e.key === 'Escape')      closeLightbox();
      if (e.key === 'ArrowLeft')   navigate(-1);
      if (e.key === 'ArrowRight')  navigate(+1);
    });
  }


  /* ========================================================
     OPEN LIGHTBOX
  ======================================================== */
  function openLightbox(postId) {
    ensureDom();
    lastFocused = document.activeElement;

    showLoading();
    $overlay.removeAttr('hidden');
    $overlay.removeAttr('aria-hidden');
    document.body.style.overflow = 'hidden';

    // Focus overlay for screen readers
    $overlay[0].focus();
    trapFocus($overlay[0]);

    loadPin(postId);
    updateNavButtons();
  }

  function closeLightbox() {
    if (!$overlay) return;
    $overlay.attr('hidden', '');
    document.body.style.overflow = '';
    // Clear video to stop playback
    $('#lightbox-video').attr('hidden', '').empty();
    // Return focus
    if (lastFocused) { try { lastFocused.focus(); } catch(e) {} }
  }


  /* ========================================================
     LOAD PIN DATA VIA AJAX
  ======================================================== */
  function loadPin(postId) {
    currentIdx = pinIds.indexOf(postId);
    showLoading();

    $.post(ajaxUrl, {
      action:  'ipin_lightbox_data',
      post_id: postId,
      nonce:   nonce,
    }, function (resp) {
      if (resp.success) {
        renderPin(resp.data);
      }
    }).fail(function () {
      hideLlighting();
    });
  }


  /* ========================================================
     RENDER PIN DATA
  ======================================================== */
  function renderPin(d) {
    // Image or video
    if (d.is_video && d.embed_url) {
      $('#lightbox-img').attr('src', '').attr('hidden', '');
      var $vid = $('#lightbox-video').removeAttr('hidden');
      $vid.html('<iframe src="' + d.embed_url + '" allowfullscreen loading="lazy" title="' + escHtml(d.title) + '"></iframe>');
    } else {
      $('#lightbox-video').attr('hidden', '').empty();
      var $img = $('#lightbox-img').removeAttr('hidden');
      $img.attr('src', d.img_url || '').attr('alt', d.title || '');
      $img.off('load error').on('load error', hideLoading);
      if ($img[0].complete) hideLoading();
    }

    // Title
    $('#lightbox-title-link').attr('href', d.permalink).text(d.title);

    // Meta
    $('#lightbox-meta').html(
      '<img src="' + escAttr(d.author_avatar) + '" width="22" height="22" alt="" style="border-radius:50%">' +
      '<a href="' + escAttr(d.author_url) + '">' + escHtml(d.author_name) + '</a>' +
      '&nbsp;·&nbsp;' + escHtml(d.date)
    );

    // Description
    $('#lightbox-desc').text(d.description || '');

    // Source link
    if (d.source_url) {
      var sourceHost = '';
      try { sourceHost = new URL(d.source_url).hostname; } catch(e) { sourceHost = d.source_url; }
      $('#lightbox-source').removeAttr('hidden').attr('href', d.source_url);
      $('#lightbox-source-text').text(sourceHost);
    } else {
      $('#lightbox-source').attr('hidden', '');
    }

    // Social actions
    renderSocialActions(d);

    // Comments preview
    if (d.comments && d.comments.length) {
      var $clist = $('#lightbox-comments-list').empty();
      d.comments.forEach(function (c) {
        $clist.append(
          '<li class="lightbox-comment-item">' +
          '<img src="' + escAttr(c.avatar) + '" alt="" loading="lazy">' +
          '<div class="lightbox-comment-item__text">' +
          '<span class="lightbox-comment-item__author">' + escHtml(c.author) + '</span>' +
          escHtml(c.text) +
          '</div></li>'
        );
      });
      $('#lightbox-view-all').attr('href', d.permalink + '#comments');
      $('#lightbox-comments').removeAttr('hidden');
    } else {
      $('#lightbox-comments').attr('hidden', '');
    }

    updateNavButtons();
  }


  /* ========================================================
     SHARE ACTIONS IN LIGHTBOX
  ======================================================== */
  function renderSocialActions(d) {
    var $sa = $('#lightbox-social-actions').empty();

    // Pinterest share
    var pinUrl = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(d.permalink)
               + '&description=' + encodeURIComponent(d.title);
    $sa.append(
      '<a class="btn-social btn-share-pinterest" href="' + pinUrl + '" target="_blank" rel="noopener noreferrer"' +
      '  aria-label="Save to Pinterest (opens in new tab)">' +
      '  <i class="fa-brands fa-pinterest" aria-hidden="true"></i>' +
      '</a>'
    );

    // Twitter / X share
    var twUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(d.permalink)
              + '&text=' + encodeURIComponent(d.title);
    $sa.append(
      '<a class="btn-social btn-share-twitter" href="' + twUrl + '" target="_blank" rel="noopener noreferrer"' +
      '  aria-label="Share on X / Twitter (opens in new tab)">' +
      '  <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>' +
      '</a>'
    );

    // Facebook share
    var fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(d.permalink);
    $sa.append(
      '<a class="btn-social btn-share-facebook" href="' + fbUrl + '" target="_blank" rel="noopener noreferrer"' +
      '  aria-label="Share on Facebook (opens in new tab)">' +
      '  <i class="fa-brands fa-facebook" aria-hidden="true"></i>' +
      '</a>'
    );

    // View full post
    $sa.append(
      '<a class="btn-social btn-view-post" href="' + d.permalink + '">' +
      '  <i class="fa fa-expand-alt" aria-hidden="true"></i> View' +
      '</a>'
    );
  }


  /* ========================================================
     NAVIGATE PREV / NEXT
  ======================================================== */
  function navigate(dir) {
    if (!pinIds.length) return;
    var newIdx = currentIdx + dir;
    if (newIdx < 0 || newIdx >= pinIds.length) return;
    currentIdx = newIdx;
    loadPin(pinIds[currentIdx]);
    updateNavButtons();
  }

  function updateNavButtons() {
    var $prev = $('#lb-prev');
    var $next = $('#lb-next');
    $prev.prop('disabled', currentIdx <= 0);
    $next.prop('disabled', currentIdx >= pinIds.length - 1);
  }


  /* ========================================================
     LOADING STATE
  ======================================================== */
  function showLoading() {
    $('#lightbox-loading').removeAttr('hidden');
    $('#lightbox-img').attr('src', '');
  }

  function hideLoading() {
    $('#lightbox-loading').attr('hidden', '');
  }


  /* ========================================================
     FOCUS TRAP
  ======================================================== */
  function trapFocus(element) {
    var sel = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    $(element).off('keydown.lbtrap').on('keydown.lbtrap', function (e) {
      if (e.key !== 'Tab') return;
      var foc = Array.from(element.querySelectorAll(sel)).filter(function (el) {
        return !el.hidden && !el.closest('[hidden]') && !el.disabled;
      });
      if (!foc.length) return;
      var first = foc[0], last = foc[foc.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
      }
    });
  }


  /* ========================================================
     INIT: collect pin IDs + bind card clicks
  ======================================================== */
  $(function () {
    // Build ordered array of post IDs from current grid
    function collectPinIds() {
      pinIds = [];
      $('#masonry .thumb[data-post-id]').each(function () {
        pinIds.push(+$(this).data('post-id'));
      });
    }

    collectPinIds();

    // Re-collect after infinite scroll appends items
    $(document).on('ipin:infiniteScrollLoaded', collectPinIds);

    // Card click — open lightbox
    $(document).on('click', '.thumb-img-wrap[data-post-id], .thumb[data-post-id] .thumb-img-wrap', function (e) {
      e.preventDefault();
      var postId = +$(this).closest('[data-post-id]').data('post-id');
      if (postId) openLightbox(postId);
    });

    // Also support Enter key on focusable card links
    $(document).on('keydown', '.thumb .thumbtitle a', function (e) {
      if (e.key === 'Enter' && e.shiftKey) {
        e.preventDefault();
        var postId = +$(this).closest('[data-post-id]').data('post-id');
        if (postId) openLightbox(postId);
      }
    });
  });


  /* ========================================================
     HELPERS
  ======================================================== */
  function escHtml(s) {
    return $('<span>').text(s || '').html();
  }

  function escAttr(s) {
    return (s || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

})(jQuery);
