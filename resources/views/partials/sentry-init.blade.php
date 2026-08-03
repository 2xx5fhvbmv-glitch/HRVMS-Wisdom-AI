{{--
    Sentry browser error tracking.

    Loaded from Sentry's own CDN bundle and initialised with values read
    from .env at render time — same reasoning as partials.pusher-init:
    no rebuild of the compiled public/js/app.js bundle needed when the DSN
    changes.

    Active on local (dev testing) and production (the Hostinger/staging
    server, which also runs with APP_ENV=production) — matches
    config/sentry.php's backend gating. Events are tagged with the
    environment below so the two are distinguishable in Sentry.
--}}
@if(app()->environment(['local', 'production']) && env('SENTRY_JS_DSN'))
<script
    src="https://browser.sentry-cdn.com/10.68.0/bundle.min.js"
    integrity="sha384-wlMK49+ZQZv/XNgqTODJLX2f9EKTANs/KbJKkutCktQh4/gXMXcLdKVnYzCo/0ck"
    crossorigin="anonymous"
></script>
<script>
    if (typeof Sentry !== 'undefined') {
        Sentry.init({
            dsn: @json(env('SENTRY_JS_DSN')),
            environment: @json(env('APP_ENV')),
        });
    }
</script>
@endif
