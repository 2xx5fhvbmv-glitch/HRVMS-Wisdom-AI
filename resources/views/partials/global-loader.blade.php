{{--
    Global page-level loader.

    Shows a full-screen spinner only for state-changing requests
    (POST / PUT / PATCH / DELETE) so idempotent GETs — dropdown cascades,
    search-as-you-type, polls — can't hold the loader open. The previous
    "any AJAX in flight" version got stuck on pages that had multiple
    concurrent or long-running GETs.

    Per-call opt-out: pass `global: false` in $.ajax options.
    Programmatic control: window.showGlobalLoader() / hideGlobalLoader().
    ESC dismisses (escape hatch if a request truly hangs).
--}}
<div id="globalLoader" role="status" aria-live="polite" aria-hidden="true">
    <div class="globalLoader-spinner"></div>
</div>
<style>
    #globalLoader {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(255, 255, 255, 0.55);
        backdrop-filter: blur(2px);
        align-items: center;
        justify-content: center;
    }
    #globalLoader.is-visible { display: flex; }
    .globalLoader-spinner {
        width: 56px;
        height: 56px;
        border: 5px solid rgba(46, 172, 179, 0.2);
        border-top-color: #2eacb3;
        border-radius: 50%;
        animation: globalLoader-spin 0.8s linear infinite;
    }
    @keyframes globalLoader-spin {
        to { transform: rotate(360deg); }
    }
</style>
<script>
(function () {
    if (window.__globalLoaderInit) return;
    window.__globalLoaderInit = true;

    var $loader = null;
    var pending = 0;
    var SHOW_DELAY_MS = 200;
    var WATCHDOG_MS = 15000;
    var showTimer = null;
    var watchdogTimer = null;

    function getLoader() {
        if (!$loader || !$loader.length) {
            $loader = (typeof jQuery !== 'undefined') ? jQuery('#globalLoader') : null;
        }
        return $loader;
    }
    function showNow() {
        var $l = getLoader();
        if ($l && $l.length) $l.addClass('is-visible').attr('aria-hidden', 'false');
    }
    function hideNow() {
        var $l = getLoader();
        if ($l && $l.length) $l.removeClass('is-visible').attr('aria-hidden', 'true');
        if (showTimer)     { clearTimeout(showTimer);     showTimer = null;     }
        if (watchdogTimer) { clearTimeout(watchdogTimer); watchdogTimer = null; }
    }
    function isMutation(method) {
        if (!method) return false;
        var m = String(method).toUpperCase();
        return m === 'POST' || m === 'PUT' || m === 'PATCH' || m === 'DELETE';
    }

    window.showGlobalLoader = showNow;
    window.hideGlobalLoader = hideNow;

    function wire() {
        if (typeof jQuery === 'undefined') { return setTimeout(wire, 30); }
        var $ = jQuery;

        var settleOne = function () {
            pending = Math.max(0, pending - 1);
            if (pending === 0) hideNow();
        };

        $(document).ajaxSend(function (_e, xhr, settings) {
            if (!settings) return;
            if (settings.global === false) return;
            if (!isMutation(settings.type || settings.method)) return;
            if (xhr.__globalLoaderTracked) return; // guard against double-counts
            xhr.__globalLoaderTracked = true;
            pending++;

            // Decrement via the XHR's xhr.always() / state hook rather than
            // jQuery's ajaxComplete event. jQuery's done/complete chain can
            // be short-circuited if a user-supplied success callback throws,
            // which then strands the loader. xhr.always() runs through the
            // deferred fail path AND we also wrap with a setTimeout so a
            // sync throw inside success can never block our settle.
            var settled = false;
            var safeSettle = function () {
                if (settled) return;
                settled = true;
                xhr.__globalLoaderTracked = false;
                settleOne();
            };
            if (typeof xhr.always === 'function') {
                xhr.always(function () { setTimeout(safeSettle, 0); });
            }
            // Belt-and-braces: if the deferred chain truly never resolves,
            // tear down via the watchdog (already armed below).

            if (pending === 1) {
                if (showTimer) clearTimeout(showTimer);
                showTimer = setTimeout(showNow, SHOW_DELAY_MS);
                if (watchdogTimer) clearTimeout(watchdogTimer);
                watchdogTimer = setTimeout(function () {
                    pending = 0;
                    hideNow();
                }, WATCHDOG_MS);
            }
        });

        $(document).on('keydown', function (e) { if (e.key === 'Escape') hideNow(); });
        $(window).on('pageshow', hideNow);
    }
    wire();
})();
</script>
