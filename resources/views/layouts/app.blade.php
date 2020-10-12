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

    <style type="text/css">
    .book-cover {
        -webkit-box-shadow: 0 3px 5px rgba(0, 0, 0, 0.4);
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.4);
    }
    </style>
    @stack('header')
</head>

<body>
    @include('layouts.html.google.manager-body')

{{--     @confirmed(false)
    @include('auth.alerts.unconfirmed')
    @endconfirmed --}}
    
    <div id="app">

        @include('layouts.header')

        <main style="overflow-x: hidden">
            
            @include('admin.components.alerts.impersonator')

            @yield('content')

        </main>

        @include('search.components.forms.global')
        
        @include('layouts.footer')

        @include('auth.modal')
        
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
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
    
    <script type="text/javascript">

jQuery.fn.checkCookie = function() {
    let cookie = $(this).data('cookie');
    let record = getCookie(cookie);
    let recordedAt = moment(record, 'x');
    let expiresAt = moment(record, 'x').add(3, 'days');
    let isExpired = moment().isSameOrAfter(expiresAt);

    if (record == null || isExpired) {
        console.log('Showing popup');
        setCookie(cookie, moment().format('x'), 3);
        return this;
    }

    console.log('Popup already shown '+recordedAt.fromNow());
    console.log('Will show again '+expiresAt.fromNow());
    return $();
};

    $("#modal-subscription").checkCookie().showAfter(3);

    if (iOS()) {
        $('.free-trial-launch').attr('href', "{{config('app.stores.ios')}}");
    } else {
        $(document).ready(function() {
            setTimeout(function() {$('#webapp-banner').slideDown()}, 500);
        });
    }

    function iOS() {

      var iDevices = [
        'iPad Simulator',
        'iPhone Simulator',
        'iPod Simulator',
        'iPad',
        'MacIntel',
        'iPhone',
        'iPod'
      ];

      if (navigator.platform) {
        while (iDevices.length) {
          if (navigator.platform === iDevices.pop()){ return true; }
        }
      }

      return false;
    }
    </script>

    @stack('scripts')

</body>
</html>
