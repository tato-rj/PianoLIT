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
        .form-transparent {
            background-color: transparent !important;
        }

        /* Change Autocomplete styles in Chrome*/
        input.form-transparent:-webkit-autofill,
        input.form-transparent:-webkit-autofill:hover, 
        input.form-transparent:-webkit-autofill:focus,
        textarea.form-transparent:-webkit-autofill,
        textarea.form-transparent:-webkit-autofill:hover,
        textarea.form-transparent:-webkit-autofill:focus,
        select.form-transparent:-webkit-autofill,
        select.form-transparent:-webkit-autofill:hover,
        select.form-transparent:-webkit-autofill:focus {
          border: 1px solid transparent;
          -webkit-box-shadow: 0 0 0px 1000px transparent inset;
          transition: background-color 5000s ease-in-out 0s;
        }

        .fadeInLeft {
            animation-duration: .2s;
        }
        .search-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

    .book-cover {
        -webkit-box-shadow: 0 3px 5px rgba(0, 0, 0, 0.4);
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.4);
    }

        .rounded, .btn {
            border-radius: 1rem !important;
        }

        input, textarea, select {
            border-radius: 1rem;
        }

        .rounded-bottom, .rounded-left {
            border-bottom-left-radius: 1rem!important;
        }
        .rounded-bottom, .rounded-right {
            border-bottom-right-radius: 1rem!important;
        }
        .rounded-right, .rounded-top {
            border-top-right-radius: 1rem!important;
        }
        .rounded-left, .rounded-top {
            border-top-left-radius: 1rem!important;
        }
        
        
        .rounded-sm {
            border-radius: .25rem!important;
        }
        .rounded-sm-bottom, .rounded-sm-left {
            border-bottom-left-radius: .25rem!important;
        }
        .rounded-sm-bottom, .rounded-sm-right {
            border-bottom-right-radius: .25rem!important;
        }
        .rounded-sm-right, .rounded-sm-top {
            border-top-right-radius: .25rem!important;
        }
        .rounded-sm-left, .rounded-sm-top {
            border-top-left-radius: .25rem!important;
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
jQuery.fn.visible = function() {
    return this.css('visibility', 'visible');
};

jQuery.fn.invisible = function() {
    return this.css('visibility', 'hidden');
};

jQuery.fn.visibilityToggle = function() {
    return this.css('visibility', function(i, visibility) {
        return (visibility == 'visible') ? 'hidden' : 'visible';
    });
};
    </script>

    <script type="text/javascript">
        var $search = $('#app-search');
        var $prevDiv = $search.prev();
        var searchPos = $search.offset();
        var mb = 0;
        console.log(searchPos);

        $(window).scroll(function() {
            let scrollTop = $(this).scrollTop();

            if (searchPos) {
                if (scrollTop > searchPos.top) {
                    mb = $search.outerHeight()+'px';
                    console.log(mb);
                    $search.addClass('search-fixed');
                    $search.find('img.animated').show();
                    $prevDiv.css('margin-bottom', mb);
                } else {
                    $search.removeClass('search-fixed');
                    $search.find('img.animated').hide();
                    $prevDiv.css('margin-bottom', '0');
                }
            }
        });
    </script>

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
            // setTimeout(function() {$('#webapp-banner').slideDown()}, 500);
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
