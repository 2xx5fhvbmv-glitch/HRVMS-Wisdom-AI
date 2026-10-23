/* WAI toast — one helper for every portal, paired with wai-toast.css.
 *
 *   wisdomToast(type, title, message, {list, sticky, undo, duration})
 *     type: 'ok'|'err'|'warn'|'info' (also accepts success/error/warning)
 *
 * It also defines a small `window.toastr` stand-in (success/error/warning/info
 * with toastr's (message, title, options) signature) so the existing calls
 * across the app render as WAI toasts without touching any page code. The
 * toastr library itself is no longer loaded.
 *
 * Text is always inserted with textContent — never as HTML. Messages that
 * arrive as HTML (e.g. "a<br>b") are reduced to plain text lines first.
 */
(function (w, d) {
    var MAX = 3, DEFAULT_MS = 5000;
    var X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>';
    var TYPE = { ok: 'ok', success: 'ok', err: 'err', error: 'err', warn: 'warn', warning: 'warn', info: 'info' };
    var EMPTY_TITLE = { ok: 'Done', err: 'Something went wrong', warn: 'Heads up', info: 'Notice' };
    var stack = null, shown = 0, queue = [];

    function node(tag, cls, text) {
        var n = d.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function getStack() {
        if (!stack) {
            stack = node('div', 'wt-stack');
            stack.setAttribute('aria-live', 'polite');
            d.body.appendChild(stack);
        }
        return stack;
    }

    function render(s) {
        var el = node('div', 'wt t-' + s.type + (s.list.length ? ' has-list' : ''));
        el.setAttribute('role', s.type === 'err' ? 'alert' : 'status');

        var body = node('div', 'wt-b');
        if (s.title) body.appendChild(node('span', 'wt-t', s.title));
        if (s.message) body.appendChild(node('span', 'wt-m', s.message));
        if (s.list.length) {
            var ul = node('ul', 'wt-list');
            s.list.forEach(function (t) { ul.appendChild(node('li', null, t)); });
            body.appendChild(ul);
        }
        var undo = null;
        if (s.undo) { undo = node('button', 'wt-undo', 'Undo'); undo.type = 'button'; body.appendChild(undo); }

        var x = node('button', 'wt-x');
        x.type = 'button';
        x.setAttribute('aria-label', 'Dismiss');
        x.innerHTML = X; // static markup defined above, no data in it

        el.appendChild(node('span', 'wt-dot'));
        el.appendChild(body);
        el.appendChild(x);

        var bar = null;
        if (!s.sticky) {
            bar = node('span', 'wt-p');
            if (s.duration !== DEFAULT_MS) el.style.setProperty('--wt-dur', s.duration + 'ms');
            el.appendChild(bar);
        }

        getStack().appendChild(el);
        shown++;

        var done = false;
        function close() {
            if (done) return;
            done = true;
            el.classList.add('is-out');
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
                shown--;
                while (shown < MAX && queue.length) render(queue.shift());
            }, 230);
        }
        x.addEventListener('click', close);
        if (bar) bar.addEventListener('animationend', close); // the countdown bar ends the toast, no JS timer
        if (undo) undo.addEventListener('click', function () { close(); s.undo(); });
        s.handle.close = close;
    }

    // Shows the toast now, or queues it when 3 are already on screen.
    function fire(s) {
        s.handle = {
            close: function () {
                var i = queue.indexOf(s);
                if (i > -1) queue.splice(i, 1);
            }
        };
        if (!d.body) {
            d.addEventListener('DOMContentLoaded', function () { fire(s); });
        } else if (shown < MAX) {
            render(s);
        } else {
            queue.push(s);
        }
        return s.handle;
    }

    w.wisdomToast = function (type, title, message, opts) {
        opts = opts || {};
        var list = (opts.list || []).map(String);
        return fire({
            type: TYPE[type] || 'info',
            title: title ? String(title) : '',
            message: message ? String(message) : '',
            list: list,
            // a validation list is sticky unless the caller says otherwise
            sticky: opts.sticky !== undefined ? !!opts.sticky : list.length > 0,
            duration: opts.duration || DEFAULT_MS,
            undo: typeof opts.undo === 'function' ? opts.undo : null
        });
    };

    /* ---------- toastr-compatible calls ---------- */

    // HTML or plain text -> array of trimmed, non-empty text lines.
    function toLines(msg) {
        var s = msg == null ? '' : String(msg);
        if (/[<&]/.test(s)) {
            s = s.replace(/<br\s*\/?>|<\/(p|div|li)>/gi, '\n');
            s = new DOMParser().parseFromString(s, 'text/html').body.textContent; // inert document: nothing runs or loads
        }
        return s.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean);
    }

    function build(type, message, title, o) {
        if (title && typeof title === 'object') { o = title; title = ''; } // toastr.error(msg, {opts}) form
        o = o || {};
        var lines = toLines(message);
        var t = title == null ? '' : String(title).trim();
        var s = { type: type, title: t, message: '', list: [], sticky: o.timeOut === 0, duration: o.timeOut > 0 ? o.timeOut : DEFAULT_MS, undo: null };
        if (lines.length > 1) {
            s.list = lines;
            if (type === 'err') {
                s.sticky = true;
                if (t) s.message = 'Please fix the following:';
                else s.title = 'Please fix the following';
            }
        } else if (t) {
            s.message = lines[0] || '';
        } else {
            s.title = lines[0] || EMPTY_TITLE[type];
        }
        return s;
    }

    // Several errors fired in the same tick (e.g. a loop over a 422 error
    // bag) become one sticky list instead of crowding the 3-toast stack.
    var burst = [], timer = 0;
    function flush() {
        var b = burst, items = [], seen = {};
        burst = []; timer = 0;
        b.forEach(function (s) {
            (s.list.length ? s.list : [s.message || s.title]).forEach(function (l) {
                if (!seen[l]) { seen[l] = 1; items.push(l); }
            });
        });
        if (b.length === 1 || items.length < 2) return fire(b[0]);
        var same = b[0].title && b.every(function (s) { return s.title === b[0].title; });
        fire({
            type: 'err',
            title: same ? b[0].title : 'Please fix the following',
            message: same ? 'Please fix the following:' : '',
            list: items, sticky: true, duration: DEFAULT_MS, undo: null
        });
    }

    function call(type) {
        return function (message, title, o) {
            var s = build(type, message, title, o);
            if (type !== 'err') return fire(s);
            burst.push(s);
            if (!timer) timer = setTimeout(flush, 0);
            return { close: function () {} };
        };
    }

    w.toastr = {
        options: {}, // accepted and ignored: look and timing are fixed by wai-toast.css
        success: call('ok'),
        error: call('err'),
        warning: call('warn'),
        info: call('info')
    };
})(window, document);
