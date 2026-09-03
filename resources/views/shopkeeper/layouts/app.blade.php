<!doctype html>
<html lang=en data-bs-theme=auto>

<head>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>

    @include('shopkeeper.layouts.css')
<head>
<body id="body-content">

    @include('shopkeeper.layouts.header')

    @yield('content')

    @include('shopkeeper.layouts.footer')

    @include('shopkeeper.layouts.js')
</body>
</html>