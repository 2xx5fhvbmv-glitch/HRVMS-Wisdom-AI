{{--
    Lightweight window.Echo shim.

    The compiled public/js/app.js bundle predates the current Pusher .env
    values, so the original Laravel Echo never initialises (key/cluster come
    out as undefined at build time and aren't re-read at runtime). Rather
    than rebuild the bundle on every env change, we load pusher-js from the
    CDN and expose a minimal Echo-compatible facade with the methods the
    existing chat code uses: Echo.channel(name).listen(event, cb).

    Values are read from config() at render time — NOT env() directly.
    A Blade view is application code, not a config file: once
    `php artisan config:cache` runs (any standard deploy), env() calls
    outside config/*.php return null while config() keeps serving the
    cached real value. With env() here, this whole @if evaluated false
    on any cached-config environment and the entire script never
    rendered — Pusher silently never even loaded, no console output at
    all, live-only (config isn't normally cached in local dev).

    Changing .env still requires config:cache to be re-run — config()
    always serves the cached snapshot, not live .env reads.
--}}
@if(config('broadcasting.connections.pusher.key') && config('broadcasting.connections.pusher.options.cluster'))
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function () {
    if (typeof Pusher === 'undefined') {
        console.warn('[pusher-shim] Pusher SDK failed to load from CDN.');
        return;
    }
    if (window.Echo && window.Echo.__shim) return;

    @if(config('app.debug'))
    Pusher.logToConsole = true;
    @endif

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var pusher = new Pusher(@json(config('broadcasting.connections.pusher.key')), {
        cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
        forceTLS: true,
        // Private/presence channels (Chat Module 1-1 + group threads) need
        // this to reach routes/web.php's /broadcasting/auth — without it
        // pusher-js falls back to the classic /pusher/auth default, which
        // doesn't exist in this app, and the subscription just silently
        // never completes.
        channelAuthorization: {
            endpoint: '/broadcasting/auth',
            transport: 'ajax',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }
    });

    pusher.connection.bind('connected', function () {
        console.log('[pusher-shim] connected to cluster ' + @json(config('broadcasting.connections.pusher.options.cluster')));
    });
    pusher.connection.bind('error', function (err) {
        console.warn('[pusher-shim] connection error', err);
    });

    // subscribeName is the RAW pusher-js channel name actually subscribed to
    // (i.e. already carrying the private-/presence- prefix, if any);
    // logName is what callers passed in, kept for readable console logs.
    function wrapChannel(subscribeName, logName) {
        var ch = pusher.subscribe(subscribeName);
        ch.bind('pusher:subscription_succeeded', function () {
            console.log('[pusher-shim] subscribed to ' + logName);
        });
        ch.bind('pusher:subscription_error', function (status) {
            console.warn('[pusher-shim] subscription_error on ' + logName, status);
        });
        return {
            listen: function (eventName, cb) {
                // Laravel's broadcast event name varies: it can be the FQCN
                // ("App\\Events\\NewChatMessage"), the FQCN with a leading
                // dot, or the short class name when broadcastAs() is set.
                // bind_global lets us match all of them by suffix.
                ch.bind_global(function (boundEvent, data) {
                    if (typeof boundEvent !== 'string') return;
                    if (boundEvent.indexOf('pusher') === 0) return;
                    var leaf = boundEvent.replace(/^\./, '').split('\\').pop();
                    if (leaf === eventName) {
                        console.log('[pusher-shim] event ' + boundEvent + ' on ' + logName);
                        cb(data);
                    }
                });
                return this;
            },
            stopListening: function () {
                pusher.unsubscribe(subscribeName);
                return this;
            }
        };
    }

    window.Echo = {
        __shim: true,
        // Public channel — no auth prefix, no handshake. Used as-is by
        // admin/resort support chat and the resort event feed.
        channel: function (name) { return wrapChannel(name, name); },
        // PrivateChannel (Chat Module 1-1 threads: 'chat.{id}').
        private: function (name) { return wrapChannel('private-' + name, name); },
        // PresenceChannel (Chat Module group threads: 'group.{id}').
        join: function (name) { return wrapChannel('presence-' + name, name); },
        connector: { pusher: pusher }
    };

    // Tiny notification ping using the Web Audio API. No external file
    // needed. Browsers block audio without prior user interaction, so the
    // first ping after page load may be silent until the user clicks /
    // types — once any interaction happens, subsequent pings play fine.
    window.playChatPing = function () {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            var ctx = window.__chatAudioCtx || (window.__chatAudioCtx = new Ctx());
            if (ctx.state === 'suspended') { try { ctx.resume(); } catch (e) {} }
            var now = ctx.currentTime;
            var play = function (freq, start, dur) {
                var o = ctx.createOscillator();
                var g = ctx.createGain();
                o.type = 'sine';
                o.frequency.value = freq;
                g.gain.setValueAtTime(0.0001, now + start);
                g.gain.exponentialRampToValueAtTime(0.25, now + start + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, now + start + dur);
                o.connect(g); g.connect(ctx.destination);
                o.start(now + start);
                o.stop(now + start + dur + 0.02);
            };
            // Two-tone ping: high then higher.
            play(880, 0,    0.12);
            play(1175, 0.10, 0.18);
        } catch (e) {}
    };
})();
</script>
@endif
