<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.manager-head')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | PianoLIT Games</title>
    <meta name="description" content="Play {{ $title }} on PianoLIT Games">
    <link rel="canonical" href="{{ $settings->gameUrl() }}">
    @include('layouts.html.theme')
    @include('layouts.html.js-app')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @include('games.challenges.assets')
    @stack('header')
</head>
<body class="music-games" data-preserve-icons>
    @include('layouts.html.google.manager-body')
    <main>@yield('content')</main>
    @include('games.challenges.components.duel')
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ mix('js/games/hub.js') }}"></script>
    @include('components.tracking.activity')
    @include('webapp.components.campaign-attribution')
    @stack('scripts')
</body>
</html>
