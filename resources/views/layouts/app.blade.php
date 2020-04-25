<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.analytics')
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')
    @include('layouts.html.google.recaptcha')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    @include('layouts.html.verify')
    
    @include('layouts.html.theme')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{local() ? '(local)' : null}} {{$title ?? config('app.name')}}</title>

    @isset($shareable)
        @include('layouts.html.shareable')
    @endisset

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')

    @stack('header')
</head>

<body>
    @include('layouts.html.google.manager-body')

    @confirmed(false)
    @include('auth.alerts.unconfirmed')
    @endconfirmed
    
    <div id="app">

        @include('layouts.header')

        <main style="overflow-x: hidden">
            
            @include('admin.components.alerts.impersonator')

            @yield('content')

        </main>

        @include('components.search.forms.global')
        
        @include('layouts.footer')

        @include('auth.modal')        
        @include('components.alerts.http')
    </div>

    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')

</body>
</html>
