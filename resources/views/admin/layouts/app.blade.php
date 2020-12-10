<!doctype html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('admin.layouts.html.theme')

    <title>{{local() ? '(local)' : null}} PianoLIT | Admin</title>

    <link rel="preload" href="{{ asset('css/vendor/fontawesome/all.min.css') }}" as="style">
    
    <link href="{{ asset('css/vendor/fontawesome/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
    
    @include('admin.layouts.html.js-app')

    @yield('head')
  </head>

  <body class="fixed-nav sticky-footer bg-dark" id="page-top">
    @include('admin.layouts.header.bar')

    @yield('content')

    {{-- @include('admin.layouts.footer') --}}

    @include('admin.components.loader')
    
    @if($message = session('status'))
    @alert([
        'color' => 'green',
        'message' => '<strong class="mr-2">Success |  </strong>' . $message,
        'dismissible' => true,
        'floating' => 'top'])
    @endif

    @if($message = session('error') ?? $errors->first())
    @alert([
        'color' => 'red',
        'message' => '<strong class="mr-2">Sorry |  </strong>' . $message,
        'dismissible' => true,
        'floating' => 'top'])
    @endif

    <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>

    <script type="text/javascript">
$(window).bind('load', function() {
    $('#loading-overlay > div').addClass('animateSlideUpOut');
    $('#loading-overlay').fadeOut(function() {
            $(this).remove();
    });
});
    </script>

    @yield('scripts')
  </body>
    
</html>
