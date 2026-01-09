<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }} | @yield('page_tab_title')</title>
  @include('admin.layouts.css')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <!-- Preloader -->
    <!-- <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__shake" src="{{ URL::asset('admin_assets/dist/img/AdminLTELogo.png') }}" alt="AdminLTELogo" height="60" width="60">
    </div> -->

    @include('admin.layouts.header')

    @if( Auth::guard('admin')->check() )
      @include('admin.layouts.sidebar')
    @endif

    <!-- Content Wrapper. Contains page content -->
    @yield('content')

    @include('admin.layouts.footer')
  </div>
  @include('admin.layouts.js')
</body>
</html>
