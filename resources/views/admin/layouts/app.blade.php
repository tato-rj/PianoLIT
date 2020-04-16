<!doctype html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('admin.layouts.html.theme')

    <title>{{local() ? '(local)' : null}} PianoLIT | Admin</title>

    <link rel="stylesheet" type="text/css" href="{{asset('css/primer.css')}}">
    <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
    
    @include('admin.layouts.html.js-app')

    @yield('head')
  </head>

  <body class="fixed-nav sticky-footer bg-dark" id="page-top">
    @include('admin.layouts.header.bar')

    @yield('content')

    @include('admin.layouts.footer')

    @include('components.loaders.fullpage')
    
    @include('components.alerts.http')

    <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>

    @yield('scripts')
  </body>
    
</html>
