/**
 * iPin Modern — Custom JS (no jQuery)
 *
 * Handles:
 *   1. Dark-mode toggle (WCAG 4.1.2 — aria-pressed, aria-label updated dynamically)
 *   2. Hamburger nav (WCAG 4.1.2 — aria-expanded)
 *   3. Scroll-to-top button (WCAG 2.5.8 — <button>, hidden until needed)
 *   4. Copy-link share button
 *
 * Grid layout and infinite scroll live in ipin.grid.js.
 */
(function () {
  'use strict';

  /* ========================================================
     1. DARK MODE
     WCAG 4.1.2: button has name (aria-label), role (button),
     value (aria-pressed). Label changes with state.
     localStorage is written ONLY on an explicit click, so a
     visitor who never toggled keeps following their OS theme
     and the admin's default.
  ======================================================== */
  var darkToggle = document.getElementById('dark-mode-toggle');
  var html       = document.documentElement;

  var LABEL_DARK  = darkToggle ? darkToggle.getAttribute('data-label-dark')  || 'Switch to dark mode'  : '';
  var LABEL_LIGHT = darkToggle ? darkToggle.getAttribute('data-label-light') || 'Switch to light mode' : '';

  function isDark() {
    return html.getAttribute('data-theme') === 'dark';
  }

  function applyDarkState(dark, persist) {
    if (dark) {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.removeAttribute('data-theme');
    }
    if (darkToggle) {
      darkToggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
      darkToggle.setAttribute('aria-label', dark ? LABEL_LIGHT : LABEL_DARK);
    }
    if (persist) {
      try { localStorage.setItem('ipin-dark-mode', dark ? 'dark' : 'light'); } catch (e) {}
    }
  }

  if (darkToggle) {
    // Sync button state with the theme the pre-paint head script applied.
    applyDarkState(isDark(), false);

    darkToggle.addEventListener('click', function () {
      applyDarkState(!isDark(), true);
    });

    // Keep in sync if the OS theme changes while the page is open
    // and the visitor has no explicit choice stored.
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener('change', function (e) {
      var stored = null;
      try { stored = localStorage.getItem('ipin-dark-mode'); } catch (err) {}
      if (!stored) {
        applyDarkState(e.matches, false);
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
      openNav(!navMain.classList.contains('open'));
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
     A <button> (WCAG 2.1.1). Uses hidden + aria-hidden until
     the page is scrolled; shown progressively.
  ======================================================== */
  var scrollBtn = document.getElementById('scrolltotop');

  if (scrollBtn) {
    var SCROLL_THRESHOLD = 400;
    var ticking = false;

    var updateScrollBtn = function () {
      var scrolled = window.scrollY || window.pageYOffset;
      if (scrolled > SCROLL_THRESHOLD) {
        scrollBtn.removeAttribute('hidden');
        scrollBtn.removeAttribute('aria-hidden');
        scrollBtn.style.opacity = '1';
        scrollBtn.style.pointerEvents = 'auto';
      } else {
        scrollBtn.style.opacity = '0';
        scrollBtn.style.pointerEvents = 'none';
        // Delay hiding until the transition ends so the CSS fade plays
        setTimeout(function () {
          if ((window.scrollY || window.pageYOffset) <= SCROLL_THRESHOLD) {
            scrollBtn.setAttribute('hidden', '');
            scrollBtn.setAttribute('aria-hidden', 'true');
          }
        }, 350);
      }
      ticking = false;
    };

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
     4. COPY-LINK SHARE BUTTON
  ======================================================== */
  var copyTimer = null;

  function confirmCopied(btn) {
    var label = btn.querySelector('.btn-share__label');
    var live  = document.getElementById('ipin-live-region');
    btn.classList.add('copied');
    if (label) {
      // Remember the real label once, so rapid re-clicks can never
      // capture "Copied!" as the text to restore.
      if (!label.dataset.originalText) {
        label.dataset.originalText = label.textContent;
      }
      label.textContent = 'Copied!';
      // Announce via the aria-live region (WCAG 4.1.3)
      if (live) live.textContent = 'Link copied to clipboard.';
    }
    if (copyTimer) clearTimeout(copyTimer);
    copyTimer = setTimeout(function () {
      btn.classList.remove('copied');
      if (label) label.textContent = label.dataset.originalText;
      if (live) live.textContent = '';
      copyTimer = null;
    }, 2000);
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
})();
