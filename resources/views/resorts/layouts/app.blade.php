<!doctype html>
<html lang=en data-bs-theme=auto>
<head>
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Dark/Teal theme system — disabled 2026-08-30, not production-ready
         (contrast/legibility issues found in the user's own testing beyond
         what the regression audit caught and fixed). This is the ONLY
         switch that ever sets data-theme on <html>; with it off, every
         var(--token)/[data-theme=...] rule already in the codebase stays
         permanently inert and the app renders exactly as it did before
         this work (Light values were verified pixel-identical). To resume
         this work later, uncomment this include (and its 5 siblings —
         see resources/views/shopkeeper/layouts/app.blade.php,
         resources/views/resorts/layouts/header.blade.php,
         resources/views/shopkeeper/layouts/header.blade.php, and the
         chart-theme.js <script> tag in both resorts/shopkeeper
         layouts/js.blade.php) and pick up the remaining known issues from
         there.
    @include('partials._theme_engine')
    --}}

    @include('partials.sentry-init')

    @include('resorts.layouts.css')

</head>
<body id="body-content " class="Dashboard-page">
    @php
    $resort_id =Auth::guard('resort-admin')->user()->resort_id;
    $user_id =  isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' ;

@endphp
        
    @include('resorts.layouts.header')

    @include('resorts.layouts.admin_broadcast_banner')

    @yield('content')

    {{-- @include('resorts.layouts.sidebar') --}}

    @include('resorts.layouts.footer')

    @include('resorts.layouts.modal')

    @include('resorts.layouts.js')

    {{-- Laravel's validation error bag currently has no universal surfacing
         anywhere in this layout (only inline @error() field messages, added
         per-page) — this is additive, not a change to an existing trigger.
         One place so every redirect-back-with-errors flow (Payroll and any
         other validation) gets the sticky multi-line toast automatically. --}}
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            wisdomToast('error', 'Please fix the following', 'A few things need attention before you can continue:', {
                list: @json($errors->all())
            });
        });
    </script>
    @endif

    @include('partials.global-loader')
</body>
</html>
