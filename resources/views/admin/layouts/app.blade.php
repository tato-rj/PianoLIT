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
<style type="text/css">
/* Works on Firefox */
.navbar-nav {
  scrollbar-width: thin;
  scrollbar-color: rgba(0,0,0,0.1) transparent;
}

/* Works on Chrome, Edge, and Safari */
.navbar-nav::-webkit-scrollbar {
  width: 6px;
}

.navbar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.navbar-nav::-webkit-scrollbar-thumb {
  background-color: rgba(0,0,0,0.1);
  border-radius: 20px;
}
</style>
    @yield('head')
  </head>

  <body class="fixed-nav sticky-footer bg-dark" id="page-top">
    @include('admin.components.loader')

    @include('admin.layouts.header.bar')

    @yield('content')

    {{-- @include('admin.layouts.footer') --}}

    
    
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
      function showLoader() {
        $('#loading-overlay').find('>div').removeClass('animateLoader').parent().show();
      }

      function hideLoader() {
        $('#loading-overlay > div:last-child').addClass('animateLoader').parent().fadeOut();
      }

      $(window).bind('beforeunload', function(){
        showLoader();
      });

      $(window).bind('load', function() {
          hideLoader();
      });
    </script>

    @yield('scripts')
  </body>
    
</html>
