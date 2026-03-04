/**
 * iPin Modern — Admin Options Page JS
 * Handles tab switching, colour scheme swatches, and AJAX save.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ── Tab switching ── */
    var tabBtns   = document.querySelectorAll('.ipin-tabs-nav button');
    var tabPanels = document.querySelectorAll('.ipin-tab-panel');

    tabBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-tab');

        tabBtns.forEach(function (b) {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });

        tabPanels.forEach(function (p) {
          p.classList.remove('active');
        });

        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');

        var panel = document.getElementById('ipin-tab-' + target);
        if (panel) panel.classList.add('active');

        // Persist active tab in sessionStorage
        try { sessionStorage.setItem('ipin_active_tab', target); } catch (e) {}
      });
    });

    // Restore last active tab
    try {
      var lastTab = sessionStorage.getItem('ipin_active_tab');
      if (lastTab) {
        var restoreBtn = document.querySelector('.ipin-tabs-nav button[data-tab="' + lastTab + '"]');
        if (restoreBtn) restoreBtn.click();
      }
    } catch (e) {}

    /* ── Colour scheme swatches ── */
    var swatches = document.querySelectorAll('.ipin-scheme-swatch');

    swatches.forEach(function (swatch) {
      swatch.addEventListener('click', function () {
        var radio = swatch.querySelector('input[type="radio"]');
        if (radio) {
          radio.checked = true;
          radio.dispatchEvent(new Event('change', { bubbles: true }));
        }
        swatches.forEach(function (s) { s.classList.remove('selected'); });
        swatch.classList.add('selected');
      });
    });

    // Mark initially selected swatch
    swatches.forEach(function (swatch) {
      var radio = swatch.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        swatch.classList.add('selected');
      }
    });

    /* ── AJAX save with "Saved!" feedback ── */
    var form       = document.getElementById('ipin-settings-form');
    var saveBar    = document.querySelector('.ipin-save-bar');
    var savedNotice = document.querySelector('.ipin-saved-notice');

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var data     = new FormData(form);
        var btn      = form.querySelector('.button-primary');
        var origText = btn ? btn.textContent : '';

        if (btn) {
          btn.textContent = ipinAdmin.saving || 'Saving…';
          btn.disabled = true;
        }

        fetch(ipinAdmin.ajaxUrl, {
          method:  'POST',
          body:    data,
          credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (btn) {
            btn.textContent = origText;
            btn.disabled = false;
          }
          if (savedNotice) {
            savedNotice.classList.add('visible');
            setTimeout(function () {
              savedNotice.classList.remove('visible');
            }, 3000);
          }
        })
        .catch(function () {
          if (btn) {
            btn.textContent = origText;
            btn.disabled = false;
          }
        });
      });
    }

  }); // DOMContentLoaded

})();
