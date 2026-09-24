/**
 * iPin Modern — Admin Settings JS
 * Design system v1.0.0
 *
 * Spec: scoped_event_binding_to_target_wrapper
 *       no_global_namespace_pollution
 */
(function () {
  'use strict';

  var root = document.querySelector('.plugin-settings-root');
  if (!root) return;

  /* ── Tab switching ─────────────────────────────────────────
     Keyboard: ArrowLeft / ArrowRight navigate between tabs.
     Active state class: ipin-active (matches both button + panel).
  ─────────────────────────────────────────────────────────── */
  var tabBtns   = Array.from(root.querySelectorAll('.ipin-tabs-nav [role="tab"]'));
  var tabPanels = Array.from(root.querySelectorAll('.ipin-tab-panel'));

  function activateTab(slug) {
    tabBtns.forEach(function (btn) {
      var on = btn.dataset.tab === slug;
      btn.classList.toggle('ipin-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
      btn.tabIndex = on ? 0 : -1;
    });
    tabPanels.forEach(function (panel) {
      panel.classList.toggle('ipin-active', panel.id === 'ipin-tab-' + slug);
    });
    try { sessionStorage.setItem('ipin_tab', slug); } catch (e) {}
  }

  tabBtns.forEach(function (btn, i) {
    btn.addEventListener('click', function () { activateTab(btn.dataset.tab); });

    btn.addEventListener('keydown', function (e) {
      var next = -1;
      if (e.key === 'ArrowRight') next = (i + 1) % tabBtns.length;
      if (e.key === 'ArrowLeft')  next = (i - 1 + tabBtns.length) % tabBtns.length;
      if (next >= 0) {
        tabBtns[next].focus();
        activateTab(tabBtns[next].dataset.tab);
        e.preventDefault();
      }
    });
  });

  // Restore last active tab across page loads
  try {
    var saved = sessionStorage.getItem('ipin_tab');
    if (saved && root.querySelector('[data-tab="' + saved + '"]')) {
      activateTab(saved);
    }
  } catch (e) {}


  /* ── Colour scheme — visual selector ───────────────────────
     Keyboard-accessible: clicking the card checks the hidden
     radio; the CSS :checked + .inner handles the border highlight.
  ─────────────────────────────────────────────────────────── */
  root.querySelectorAll('.ipin-scheme-card input[type="radio"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      root.querySelectorAll('.ipin-scheme-card__inner').forEach(function (inner) {
        inner.setAttribute('aria-checked', 'false');
      });
      radio.nextElementSibling.setAttribute('aria-checked', 'true');
    });
  });


  /* ── Range slider — live value display ──────────────────────
     Reads input[type=range]#ipin_card_width, updates sibling
     #ipin_card_width_val span in real time.
  ─────────────────────────────────────────────────────────── */
  var slider    = root.querySelector('#ipin_card_width');
  var sliderOut = root.querySelector('#ipin_card_width_val');

  if (slider && sliderOut) {
    slider.addEventListener('input', function () {
      sliderOut.textContent = slider.value + 'px';
    });
  }


  /* ── AJAX save ──────────────────────────────────────────────
     Submits all form fields via fetch(). Shows saved notice
     briefly on success, restores button on error.
  ─────────────────────────────────────────────────────────── */
  var form      = root.querySelector('#ipin-settings-form');
  var saveBtn   = root.querySelector('.ipin-btn-primary[type="submit"]');
  var savedNote = root.querySelector('.ipin-saved-notice');

  if (!form || !saveBtn) return;
  if (typeof ipinAdmin === 'undefined') return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var originalLabel   = saveBtn.textContent;
    saveBtn.disabled    = true;
    saveBtn.textContent = ipinAdmin.saving || 'Saving\u2026';

    var data = new FormData(form);
    data.set('action', 'ipin_save_options');
    data.set('ipin_nonce', ipinAdmin.nonce);

    fetch(ipinAdmin.ajaxUrl, { method: 'POST', body: data })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        saveBtn.disabled    = false;
        saveBtn.textContent = originalLabel;
        if (res && res.success && savedNote) {
          savedNote.classList.add('ipin-visible');
          setTimeout(function () { savedNote.classList.remove('ipin-visible'); }, 2500);
        }
      })
      .catch(function () {
        saveBtn.disabled    = false;
        saveBtn.textContent = originalLabel;
      });
  });

}());
