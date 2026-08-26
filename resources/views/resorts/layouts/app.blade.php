<!doctype html>
<html lang=en data-bs-theme=auto>
<head>
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
