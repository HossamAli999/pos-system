<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title')</title>

        <link rel="stylesheet" href="{{ asset('css/vendor.css') }}">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">

        @yield('css')
    </head>

    <body class="landing-body">
        @include('layouts.partials.home_header')
        @yield('content')
        @include('layouts.partials.javascripts')

        <!-- Scripts -->
        <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>
        @yield('javascript')
    </body>
</html>
