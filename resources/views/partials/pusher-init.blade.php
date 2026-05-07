{{--
    Lightweight window.Echo shim.

    The compiled public/js/app.js bundle predates the current Pusher .env
    values, so the original Laravel Echo never initialises (key/cluster come
    out as undefined at build time and aren't re-read at runtime). Rather
    than rebuild the bundle on every env change, we load pusher-js from the
    CDN and expose a minimal Echo-compatible facade with the methods the
    existing chat code uses: Echo.channel(name).listen(event, cb).

    Values are read from .env at render time so changing .env + clearing
    config is enough — no JS rebuild required.

    Diagnostics: all subscribe/disconnect/error events are logged to the
    console under the [pusher-shim] prefix. window.Echo.connector.pusher
    exposes the underlying Pusher instance for ad-hoc debugging.
--}}
@if(env('PUSHER_APP_KEY') && env('PUSHER_APP_CLUSTER'))
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

    var pusher = new Pusher(@json(env('PUSHER_APP_KEY')), {
        cluster: @json(env('PUSHER_APP_CLUSTER')),
        forceTLS: true
    });

    pusher.connection.bind('connected', function () {
        console.log('[pusher-shim] connected to cluster ' + @json(env('PUSHER_APP_CLUSTER')));
    });
    pusher.connection.bind('error', function (err) {
        console.warn('[pusher-shim] connection error', err);
    });

    function makeChannel(name) {
        var ch = pusher.subscribe(name);
        ch.bind('pusher:subscription_succeeded', function () {
            console.log('[pusher-shim] subscribed to ' + name);
        });
        ch.bind('pusher:subscription_error', function (status) {
            console.warn('[pusher-shim] subscription_error on ' + name, status);
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
                        console.log('[pusher-shim] event ' + boundEvent + ' on ' + name);
                        cb(data);
                    }
                });
                return this;
            },
            stopListening: function () {
                pusher.unsubscribe(name);
                return this;
            }
        };
    }

    window.Echo = {
        __shim: true,
        channel: makeChannel,
        // Private/presence channels need /broadcasting/auth wiring; the
        // chat module currently uses public channels, so route private()
        // through the same path as a fallback.
        private: makeChannel,
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
