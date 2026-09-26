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
  root.classList.add('ipin-js');   // switches the CSS from stacked sections to tabs

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


  /* ── Range sliders — live value display ─────────────────────
     Each input[type=range] updates the #<id>_val span beside it.
     data-zero-label names the 0 value (Image height: "Natural").
  ─────────────────────────────────────────────────────────── */
  root.querySelectorAll('input[type="range"]').forEach(function (slider) {
    var out = root.querySelector('#' + slider.id + '_val');
    if (!out) return;
    slider.addEventListener('input', function () {
      out.textContent = (slider.value === '0' && slider.dataset.zeroLabel)
        ? slider.dataset.zeroLabel
        : slider.value + 'px';
    });
  });


  /* ── Hidden-tag filter ──────────────────────────────────────
     Narrows the Visibility tab's tag list as you type. Ticked
     boxes stay in the form whether shown or not.
  ─────────────────────────────────────────────────────────── */
  var tagFilter = root.querySelector('#ipin-tag-filter');
  if (tagFilter) {
    var tagItems = Array.from(root.querySelectorAll('#ipin-tag-list li'));
    tagFilter.addEventListener('input', function () {
      var q = tagFilter.value.trim().toLowerCase();
      tagItems.forEach(function (li) {
        li.hidden = q !== '' && li.dataset.name.indexOf(q) === -1;
      });
    });
    // Enter in the filter must not submit the settings form.
    tagFilter.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') e.preventDefault();
    });
  }


  /* ── AJAX save ──────────────────────────────────────────────
     Progressive enhancement: without JS the form posts to
     options.php. With JS it saves via fetch() and reports the
     result in the save bar whose button was pressed — every
     tab has one. Failures are shown, never swallowed.
  ─────────────────────────────────────────────────────────── */
  var form = root.querySelector('#ipin-settings-form');

  if (!form) return;
  if (typeof ipinAdmin === 'undefined') return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var btn   = (e.submitter && e.submitter.closest('.ipin-save-bar'))
      ? e.submitter
      : root.querySelector('.ipin-tab-panel.ipin-active .ipin-btn-primary') || root.querySelector('.ipin-btn-primary');
    var bar   = btn.closest('.ipin-save-bar');
    var saved = bar.querySelector('.ipin-saved-notice');
    var error = bar.querySelector('.ipin-error-notice');

    var originalLabel = btn.textContent;
    btn.disabled    = true;
    btn.textContent = ipinAdmin.saving || 'Saving…';
    if (error) { error.hidden = true; error.textContent = ''; }

    var data = new FormData(form);
    data.set('action', 'ipin_save_options');
    data.set('ipin_nonce', ipinAdmin.nonce);

    function fail(message) {
      if (!error) return;
      error.textContent = message || ipinAdmin.error;
      error.hidden = false;
    }

    fetch(ipinAdmin.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return null; });
      })
      .then(function (res) {
        btn.disabled    = false;
        btn.textContent = originalLabel;
        if (res && res.success) {
          if (saved) {
            saved.classList.add('ipin-visible');
            setTimeout(function () { saved.classList.remove('ipin-visible'); }, 2500);
          }
        } else {
          fail(res && res.data && res.data.message);
        }
      })
      .catch(function () {
        btn.disabled    = false;
        btn.textContent = originalLabel;
        fail();
      });
  });

}());
