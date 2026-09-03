{{--
    Dark/Teal theme engine — resort-admin + shopkeeper only (admin portal
    untouched). One shared include: the no-flash detector (must run before
    first paint, so include this at the very top of <head>, before any
    CSS), the click handler for the picker in the profile dropdown
    (partials._theme_picker), and the picker's own small scoped CSS.
    Persistence is localStorage only — no backend, no route, no DB.
--}}
<script>
(function () {
    var KEY = 'wai-theme';
    var stored = null;
    try { stored = localStorage.getItem(KEY); } catch (e) {}
    var theme = stored;
    if (theme === null) {
        // First visit, nothing saved yet — honour the OS preference once.
        theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : '';
    }
    if (theme === 'dark' || theme === 'teal') {
        document.documentElement.setAttribute('data-theme', theme);
    }
})();
</script>
<style>
/* flex-wrap on the row + the picker's own margin-left:auto is the actual
   fix for the regression-audit overflow bug: the picker (flex:none, 3
   buttons) was wider than the space left beside the icon+label in a
   normal-width dropdown, and with no wrap it overflowed the menu's edge
   and clipped the Teal button. Tighter button padding also helps it fit
   on one line at typical widths; the wrap is what guarantees all three
   stay visible/clickable even when it doesn't (narrow menu, long label
   translation, etc.) — it just drops to its own line instead of clipping. */
.wt-theme-row{display:flex;align-items:center;flex-wrap:wrap;gap:6px 10px;padding:9px 20px}
.wt-theme-row .img-box i{font-size:14px;color:var(--muted,#5D6F75)}
.wt-theme-label{flex:1;min-width:0;color:var(--darkblack,#222)}
.wt-theme-picker{display:flex;gap:2px;background:var(--neutral-bg,#DEDEDE);border-radius:20px;padding:2px;flex:none;margin-left:auto}
.wt-theme-btn{border:none;background:none;font-family:inherit;font-size:11px;font-weight:600;line-height:1.4;padding:4px 7px;border-radius:16px;cursor:pointer;color:var(--muted,#5D6F75);transition:background .12s,color .12s;white-space:nowrap}
.wt-theme-btn.wt-active{background:var(--teal,#014653);color:#fff}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var KEY = 'wai-theme';
    var buttons = document.querySelectorAll('.wt-theme-btn');
    if (!buttons.length) return;

    function markActive(theme) {
        var current = theme || 'light';
        buttons.forEach(function (b) {
            b.classList.toggle('wt-active', b.dataset.theme === current);
        });
    }
    function apply(theme) {
        if (theme === 'dark' || theme === 'teal') {
            document.documentElement.setAttribute('data-theme', theme);
        } else {
            document.documentElement.removeAttribute('data-theme');
            theme = '';
        }
        try { localStorage.setItem(KEY, theme); } catch (e) {}
        markActive(theme);
        // Chart.js paints to <canvas> — CSS variables can't reach it, so
        // chart-theme.js listens for this to repaint charts with the new
        // theme's colours immediately (see resorts_assets/js/chart-theme.js).
        document.dispatchEvent(new CustomEvent('wai:theme-change', { detail: { theme: theme } }));
    }

    markActive(document.documentElement.getAttribute('data-theme'));
    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            apply(b.dataset.theme === 'light' ? '' : b.dataset.theme);
        });
    });
});
</script>
