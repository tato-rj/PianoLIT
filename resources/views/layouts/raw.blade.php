<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.analytics')
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')
    @include('layouts.html.google.recaptcha')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.html.theme')

    <title>{{local() ? '(local)' : null}} {{config('app.name')}}</title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')

    @stack('header')
</head>
<body>
    @include('layouts.html.google.manager-body')

    @yield('content')

    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')

</body>
</html>
