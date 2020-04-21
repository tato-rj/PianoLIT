<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.analytics')
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.html.theme')

    <title>{{local() ? '(local)' : null}} {{config('app.name')}}</title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')

    <style type="text/css">
        #webapp .menu-link {
            text-decoration: none;
            color: #969ba0;
            transition: .2s;
        }

        #webapp .menu-link:hover, #webapp .menu-link.active {
            color: #0055fe !important;
        }

        #webapp .menu-link .menu-icon {
            font-size: 1.4em;
        }
    </style>

    @stack('header')
</head>
<body>
    @include('layouts.html.google.manager-body')

    @confirmed(false)
    @include('auth.alerts.unconfirmed')
    @endconfirmed
    
    <div id="webapp" class="container pb-5">

        <div class="row">
            <div class="col-lg-8 col-md-10 col-12 mx-auto">

            @include('webapp.layouts.header')

            <main>
                
                @include('admin.components.alerts.impersonator')

                @yield('content')

            </main>
            
            @include('webapp.layouts.footer')

            @include('webapp.layouts.menu')
            </div>

        </div>

        @include('components/alerts/http')
    </div>

    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')

</body>
</html>
