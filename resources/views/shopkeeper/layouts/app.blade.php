<!doctype html>
<html lang=en data-bs-theme=auto>

<head>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>

    {{-- Dark/Teal theme system — disabled 2026-08-30, not production-ready.
         See the matching comment in resources/views/resorts/layouts/app.blade.php
         for the full explanation; this is one of 6 switches, all must be
         uncommented together to resume.
    @include('partials._theme_engine')
    --}}

    @include('shopkeeper.layouts.css')
<head>
<body id="body-content">

    @include('shopkeeper.layouts.header')

    @yield('content')

    @include('shopkeeper.layouts.footer')

    @include('shopkeeper.layouts.js')
</body>
</html>