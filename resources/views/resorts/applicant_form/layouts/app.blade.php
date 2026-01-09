<!doctype html>
<html lang=en data-bs-theme=auto>
<head>
    <title>{{ config('app.name') }} | @yield('page_tab_title')</title>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('resorts.applicant_form.layouts.css')

</head>
<body id="body-content">
     <div class="skeleton-wrapper">
        <div class="skeleton-header"></div>
        <div class="skeleton-main">
            <div class="skeleton-head"></div>
            <div class="skeleton-row">
                <div class="skeleton-firstBlock">
                    <div class="skeleton-block"></div>
                </div>
                <div class="skeleton-secondBlock">
                    <div class="skeleton-block"></div>
                </div>
                <div class="skeleton-thirdBlock">
                    <div class="skeleton-block"></div>
                </div>
            </div>
        </div>
    </div>
    @include('resorts.applicant_form.layouts.header')

    @yield('content')

    @include('resorts.applicant_form.layouts.footer')

    @include('resorts.applicant_form.layouts.js')
</body>
</html>
