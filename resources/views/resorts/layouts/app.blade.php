<!doctype html>
<html lang=en data-bs-theme=auto>
<head>
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('resorts.layouts.css')

</head>
<body id="body-content " class="Dashboard-page">
    @php
    $resort_id =Auth::guard('resort-admin')->user()->resort_id;
    $user_id =  isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' ;

@endphp
        
    @include('resorts.layouts.header')

    @yield('content')

    {{-- @include('resorts.layouts.sidebar') --}}

    @include('resorts.layouts.footer')

    @include('resorts.layouts.modal')

    @include('resorts.layouts.js')
</body>
</html>
