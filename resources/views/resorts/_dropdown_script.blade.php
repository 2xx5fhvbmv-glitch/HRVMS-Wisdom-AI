{{--
    WISDOM AI — standard dropdown/select behavior (shared, app-wide).
    One event-delegated module for every .dd on the page — include this
    partial once per page (regardless of how many dropdowns it has), no
    per-element init. Pairs with resorts._dropdown_styles.

    Mode 1 — menu of links: .dd-item is an <a href="...">. Nothing to
    wire; clicking navigates normally, this script only opens/closes/filters.

    Mode 2 — mirrors a real <select> (progressive enhancement): the .dd
    root carries data-target="#the-select-id" and each .dd-item carries a
    matching data-value. Picking an item sets the real select's value and
    dispatches an actual `change` event (bubbles), so existing jQuery
    .on('change', ...) handlers, cascades and validation keep firing
    unchanged — the select is the real submitted field the whole time,
    this is presentation only.

    Cascading selects (e.g. category -> AJAX-repopulated sub-category):
    after your existing handler replaces the target select's <option>s
    (e.g. `$('#offenses_1').html(newOptions)`), call
    `window.wisdomDD.rebuild('#offenses_1')` once to rebuild that select's
    .dd item list from its new options — nothing else about the cascade
    changes, this just keeps the visual list in sync with the real field.
--}}
<script>
(function () {
    function closeDd(dd) {
        dd.classList.remove('open');
        var t = dd.querySelector('.dd-trigger');
        if (t) t.setAttribute('aria-expanded', 'false');
    }
    function closeAll(except) {
        document.querySelectorAll('.dd.open').forEach(function (dd) {
            if (dd !== except) closeDd(dd);
        });
    }
    // Mode 2: sync a trigger's label/active item from the real select's
    // CURRENT value. Called on page load and again every time the panel
    // opens — cheap and idempotent, and the "every open" call is what
    // makes a .dd built dynamically after page load (e.g. an inline-edit
    // row injected via JS, or a select whose value changed programmatically)
    // work correctly with zero extra per-page wiring.
    function syncFromSelect(dd) {
        var targetSel = dd.getAttribute('data-target');
        if (!targetSel) return;
        var select = document.querySelector(targetSel);
        if (!select) return;
        var lbl = dd.querySelector('.dd-lbl');
        // Capture the trigger's authored placeholder ("Select Category" etc)
        // once, the first time this runs (page load, before any value can
        // have changed) — the fallback below needs it for the "cleared back
        // to empty" case, where there's no matching item and the blank
        // <option> has no text of its own to show instead.
        if (lbl && dd.dataset.defaultLabel === undefined) {
            dd.dataset.defaultLabel = lbl.textContent;
        }
        var opt = select.options[select.selectedIndex];
        var value = opt ? opt.value : '';
        var item = dd.querySelector('.dd-item[data-value="' + CSS.escape(value) + '"]');
        dd.querySelectorAll('.dd-item').forEach(function (el) { el.classList.remove('active'); });
        if (item) {
            item.classList.add('active');
            if (lbl) {
                var nm = item.querySelector('.dd-nm');
                lbl.textContent = nm ? nm.textContent : item.textContent.trim();
            }
        } else if (lbl) {
            lbl.textContent = (opt && opt.textContent.trim()) || dd.dataset.defaultLabel || '';
        }
    }

    function openDd(dd) {
        closeAll(dd);
        syncFromSelect(dd);
        dd.classList.add('open');
        var t = dd.querySelector('.dd-trigger');
        if (t) t.setAttribute('aria-expanded', 'true');
        var q = dd.querySelector('.dd-search input');
        if (q) setTimeout(function () { q.focus(); }, 0);
    }

    function pickItem(dd, item) {
        var isLink = item.tagName === 'A' && item.hasAttribute('href') && !item.hasAttribute('data-value');
        if (isLink) { closeDd(dd); return; } // let the browser navigate

        dd.querySelectorAll('.dd-item').forEach(function (el) { el.classList.remove('active'); });
        item.classList.add('active');

        var lbl = dd.querySelector('.dd-lbl');
        var nm = item.querySelector('.dd-nm');
        if (lbl) lbl.textContent = nm ? nm.textContent : item.textContent.trim();

        var targetSel = dd.getAttribute('data-target');
        if (targetSel && item.hasAttribute('data-value')) {
            var select = document.querySelector(targetSel);
            if (select) {
                select.value = item.getAttribute('data-value');
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        closeDd(dd);
    }

    function filterDd(dd, query) {
        var q = query.trim().toLowerCase();
        var list = dd.querySelector('.dd-scroll');
        if (!list) return;
        var shown = 0;
        list.querySelectorAll('.dd-item').forEach(function (el) {
            var match = !q || el.textContent.toLowerCase().indexOf(q) !== -1;
            el.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        var empty = list.querySelector('.dd-empty');
        if (shown === 0) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'dd-empty';
                empty.textContent = 'No matches.';
                list.appendChild(empty);
            }
        } else if (empty) {
            empty.remove();
        }
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.dd-trigger');
        if (trigger) {
            e.preventDefault();
            if (trigger.disabled || trigger.getAttribute('aria-disabled') === 'true') return;
            var dd = trigger.closest('.dd');
            dd.classList.contains('open') ? closeDd(dd) : openDd(dd);
            return;
        }
        var item = e.target.closest('.dd-item');
        if (item) {
            var dd2 = item.closest('.dd');
            if (dd2) pickItem(dd2, item);
            return;
        }
        if (!e.target.closest('.dd')) closeAll();
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches('.dd-search input')) {
            var dd = e.target.closest('.dd');
            if (dd) filterDd(dd, e.target.value);
        }
    });

    // Belt-and-suspenders for pages that set a mirrored select's value
    // programmatically elsewhere — `$('#x').val(v).trigger('change')`, or
    // (as several cascades in this app do) the namespaced
    // `.trigger('change.select2')` left over from the old select2 wiring.
    // Auto-resync that select's .dd on any change — redundant with
    // pickItem()'s own sync on a manual click but a no-op there, so pages
    // don't need an explicit wisdomDD.sync() call after every assignment.
    // Bound via jQuery, not addEventListener: jQuery's own .trigger() —
    // namespaced or not — invokes jQuery-registered handlers directly
    // without necessarily dispatching a real native DOM event, so a plain
    // addEventListener('change', ...) listener never sees it.
    // Deferred to DOMContentLoaded, not called inline: this partial sits
    // inside the page's content block, which every layout here renders
    // BEFORE the footer include that actually loads jQuery — so
    // window.jQuery is always undefined at the point this IIFE itself
    // runs. Waiting for DOMContentLoaded (which only fires once the whole
    // document, jQuery's own script tag included, has been parsed) is
    // what makes the check meaningful.
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
            // Bound to both the plain event and the '.select2' namespace:
            // a namespaced jQuery trigger (`.trigger('change.select2')`)
            // does NOT invoke a plainly-bound `.on('change', ...)` handler
            // — jQuery only fires handlers whose own namespace matches the
            // trigger's — so a plain binding alone silently misses every
            // leftover `.trigger('change.select2')` call from the old
            // select2 wiring. Listing the namespace here too is what makes
            // this actually catch that case, instead of just claiming to.
            window.jQuery(document).on('change change.select2', '.dd-native-select', function (e) {
                syncDd('#' + e.target.id);
            });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    // Mode 2: seed every trigger's label from its real select's current
    // value on load, so the visual dropdown starts in sync with the
    // actual submitted field (existing pre-selected value) with no extra
    // per-page JS. A .dd injected later (e.g. an inline-edit row) gets the
    // same treatment for free the first time it's opened, via openDd().
    document.querySelectorAll('.dd[data-target]').forEach(syncFromSelect);

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function rebuildDd(targetSel) {
        var select = document.querySelector(targetSel);
        if (!select) return;
        document.querySelectorAll('.dd[data-target="' + CSS.escape(targetSel) + '"]').forEach(function (dd) {
            var list = dd.querySelector('.dd-scroll');
            if (!list) return;
            var tick = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            // A caller that toggles `option.disabled` on the real select
            // (e.g. filtering positions by budget status) expects that to
            // hide the option from the visible list too — skip it here
            // rather than rendering a clickable item for something the
            // underlying <select> would refuse to submit as disabled.
            list.innerHTML = Array.prototype.map.call(select.options, function (opt) {
                if (opt.disabled) return '';
                return '<div class="dd-item" role="option" data-value="' + escHtml(opt.value) + '"><span class="dd-nm">' + escHtml(opt.textContent.trim()) + '</span>' + tick + '</div>';
            }).join('');
            syncFromSelect(dd);
        });
    }

    function syncDd(targetSel) {
        document.querySelectorAll('.dd[data-target="' + CSS.escape(targetSel) + '"]').forEach(syncFromSelect);
    }

    // Exposed for pages that set a select's value programmatically
    // (`$('#x').val(v).trigger('change')`) or repopulate its <option>s via
    // AJAX (a category -> sub-category cascade): call sync()/rebuild()
    // with the select's selector afterward to keep its .dd in step.
    window.wisdomDD = { rebuild: rebuildDd, sync: syncDd };
})();
</script>
