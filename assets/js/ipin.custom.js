/**
 * iPin Modern — Custom JS
 *
 * Handles:
 *   1. Dark-mode toggle (WCAG 4.1.2 — aria-pressed, aria-label updated dynamically)
 *   2. Hamburger nav (WCAG 4.1.2 — aria-expanded)
 *   3. Scroll-to-top button (WCAG 2.5.8 — <button>, hidden until needed)
 *   4. Masonry init (aria-busy toggled)
 *   5. Infinite scroll
 */
(function ($) {
  'use strict';

  var data = window.ipinData || {};

  /* ========================================================
     1. DARK MODE
     WCAG 4.1.2: button has name (aria-label), role (button),
     value (aria-pressed). Label changes with state.
  ======================================================== */
  var darkToggle  = document.getElementById('dark-mode-toggle');
  var darkIcon    = darkToggle ? darkToggle.querySelector('i') : null;
  var html        = document.documentElement;

  var LABEL_DARK  = darkToggle ? darkToggle.getAttribute('data-label-dark')  || 'Switch to dark mode'  : '';
  var LABEL_LIGHT = darkToggle ? darkToggle.getAttribute('data-label-light') || 'Switch to light mode' : '';

  function isDark() {
    return html.getAttribute('data-theme') === 'dark';
  }

  function applyDarkState(dark) {
    if (dark) {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.removeAttribute('data-theme');
    }
    if (darkToggle) {
      darkToggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
      darkToggle.setAttribute('aria-label', dark ? LABEL_LIGHT : LABEL_DARK);
    }
    if (darkIcon) {
      darkIcon.className = dark ? 'fa fa-sun' : 'fa fa-moon';
    }
    try { localStorage.setItem('ipin-dark-mode', dark ? 'dark' : 'light'); } catch (e) {}
  }

  // Sync button state with the current theme (set by inline script before paint)
  if (darkToggle) {
    applyDarkState(isDark());

    darkToggle.addEventListener('click', function () {
      applyDarkState(!isDark());
    });

    // Keep in sync if OS theme changes while page is open
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener('change', function (e) {
      var stored = null;
      try { stored = localStorage.getItem('ipin-dark-mode'); } catch (err) {}
      if (!stored) {
        applyDarkState(e.matches);
      }
    });
  }


  /* ========================================================
     2. HAMBURGER MENU
     WCAG 4.1.2: aria-expanded reflects open/closed state.
     WCAG 2.1.1: Escape key closes menu.
  ======================================================== */
  var hamburger = document.querySelector('.navbar-toggle');
  var navMain   = document.getElementById('nav-main');

  function openNav(open) {
    if (!hamburger || !navMain) return;
    if (open) {
      navMain.classList.add('open');
      hamburger.setAttribute('aria-expanded', 'true');
      hamburger.setAttribute('aria-label', hamburger.getAttribute('data-label-close') || 'Close navigation menu');
    } else {
      navMain.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
      hamburger.setAttribute('aria-label', hamburger.getAttribute('data-label-open') || 'Open navigation menu');
    }
  }

  if (hamburger && navMain) {
    hamburger.addEventListener('click', function () {
      var isOpen = navMain.classList.contains('open');
      openNav(!isOpen);
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#topmenu')) {
        openNav(false);
      }
    });

    // Close on Escape (WCAG 2.1.1)
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && navMain.classList.contains('open')) {
        openNav(false);
        hamburger.focus(); // return focus to trigger (WCAG 2.4.3)
      }
    });
  }


  /* ========================================================
     3. SCROLL-TO-TOP
     Now a <button> (WCAG 2.1.1). Uses hidden + aria-hidden
     until page is scrolled; shown progressively.
  ======================================================== */
  var scrollBtn = document.getElementById('scrolltotop');

  if (scrollBtn) {
    var SCROLL_THRESHOLD = 400;
    var ticking = false;

    function updateScrollBtn() {
      var scrolled = window.scrollY || window.pageYOffset;
      if (scrolled > SCROLL_THRESHOLD) {
        scrollBtn.removeAttribute('hidden');
        scrollBtn.removeAttribute('aria-hidden');
        scrollBtn.style.opacity = '1';
        scrollBtn.style.pointerEvents = 'auto';
      } else {
        scrollBtn.style.opacity = '0';
        scrollBtn.style.pointerEvents = 'none';
        // Delay hiding until transition ends so the CSS fade plays
        setTimeout(function () {
          if ((window.scrollY || window.pageYOffset) <= SCROLL_THRESHOLD) {
            scrollBtn.setAttribute('hidden', '');
            scrollBtn.setAttribute('aria-hidden', 'true');
          }
        }, 350);
      }
      ticking = false;
    }

    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(updateScrollBtn);
        ticking = true;
      }
    }, { passive: true });

    scrollBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      // Move focus to the skip link / top of page for keyboard users
      var skip = document.querySelector('.skip-link');
      if (skip) {
        skip.focus();
      }
    });
  }


  /* ========================================================
     4. MASONRY INIT
     aria-busy removed after init so AT announces the grid.
  ======================================================== */
  $(function () {
    var $masonry = $('#masonry');
    if (!$masonry.length) return;

    $masonry.imagesLoaded(function () {
      $masonry
        .masonry({
          itemSelector:  '.thumb',
          columnWidth:   '.thumb',
          gutter:        parseInt(getComputedStyle(document.documentElement)
                           .getPropertyValue('--card-gap')) || 14,
          fitWidth:      true,
        })
        .css('visibility', 'visible')
        .attr('aria-busy', 'false'); // grid is ready for AT

      $('#ajax-loader-masonry').fadeOut(200);
    });

    /* ── Infinite scroll ── */
    if (typeof $.fn.infinitescroll === 'function') {
      $masonry.infinitescroll({
        navSelector:  '#navigation',
        nextSelector: '#navigation-next a',
        itemSelector: '.thumb',
        loading: {
          msgText:   '<em>' + (data.allLoaded || 'Loading…') + '</em>',
          finishedMsg: '<em>' + (data.allLoaded || 'All items loaded') + '</em>',
        },
        errorCallback: function () {
          $('#infscr-loading').fadeOut();
        },
      }, function (newElements) {
        var $new = $(newElements);
        $new.css({ opacity: 0 });
        $masonry.masonry('appended', $new, true);
        $masonry.imagesLoaded(function () {
          $new.animate({ opacity: 1 }, 300);
        });
      });
    }
  });

})(jQuery);

/* ── Copy-link share button ───────────────────────────── */
document.addEventListener('click', function (e) {
  var btn = e.target.closest('.btn-share--copy');
  if (!btn) return;
  var url = btn.dataset.copyUrl || window.location.href;
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url).then(function () {
      confirmCopied(btn);
    }).catch(function () {
      fallbackCopy(url, btn);
    });
  } else {
    fallbackCopy(url, btn);
  }
});

function confirmCopied(btn) {
  var label = btn.querySelector('.btn-share__label');
  btn.classList.add('copied');
  if (label) {
    var orig = label.textContent;
    label.textContent = 'Copied!';
    // Announce to screen readers via the aria-live region (WCAG 4.1.3)
    var live = document.getElementById('ipin-live-region');
    if (live) live.textContent = 'Link copied to clipboard.';
    setTimeout(function () {
      label.textContent = orig;
      btn.classList.remove('copied');
      if (live) live.textContent = '';
    }, 2000);
  } else {
    setTimeout(function () { btn.classList.remove('copied'); }, 2000);
  }
}

function fallbackCopy(text, btn) {
  var ta = document.createElement('textarea');
  ta.value = text;
  ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); confirmCopied(btn); } catch (err) {}
  document.body.removeChild(ta);
}
