<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')
    @include('layouts.html.google.recaptcha')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('layouts.html.verify')
    
    @include('layouts.html.theme')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{local() ? '(local)' : null}} {{$title ?? seo()->about('title')}}</title>

    @isset($shareable)
        @include('layouts.html.shareable')
    @else
        <meta name="keywords" content="{{seo()->keywords()}}">
        <meta name="description" content="{{seo()->about('description')}}">
        <meta name="twitter:card" value="{{seo()->about('description')}}">
        <meta name="twitter:site" content="@litpiano">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{seo()->about('title')}}">
        <meta name="twitter:description" content="{{seo()->about('description')}}">
        <meta name="twitter:app:country" content="US">
        <meta name="twitter:app:name:iphone" content="PianoLIT">
        <meta name="twitter:app:id:iphone" content="00000000">

        <meta itemprop="name" content="{{seo()->about('title')}}"/>
        <meta itemprop="headline" content="{{seo()->about('description')}}"/>
        <meta itemprop="description" content="{{seo()->about('description')}}"/>
        <meta itemprop="author" content="PianoLIT"/>

        <link rel="canonical" href="{{url()->current()}}" />
    @endisset

    <link rel="preload" href="{{ asset('css/vendor/fontawesome/all.min.css') }}" as="style">

    <link href="{{ asset('css/vendor/fontawesome/all.min.css') }}" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')

    <style type="text/css">
.dropdown-toggle::after {
    display: none;
}
        .ad-banner:nth-child(odd) {
            background-color: #f8f9fa;   
        }

@-webkit-keyframes fadeInUp {
  from {
    opacity: 0;
    -webkit-transform: translate3d(0, 12%, 0);
    transform: translate3d(0, 12%, 0);
  }

  to {
    opacity: 1;
    -webkit-transform: translate3d(0, 0, 0);
    transform: translate3d(0, 0, 0);
  }
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    -webkit-transform: translate3d(0, 12%, 0);
    transform: translate3d(0, 12%, 0);
  }

  to {
    opacity: 1;
    -webkit-transform: translate3d(0, 0, 0);
    transform: translate3d(0, 0, 0);
  }
}

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

        .grid {
            opacity: 0; 
            transform: translateY(20px);
            transition: .2s;
        }

    /*PAGINATION*/
    .page-item:last-child .page-link {border-radius: 0}

    .page-link {
        /*border: none;*/
        border-radius: 8px !important;
        transition: .2s;
        margin: 2px;
    }

    .page-item:not(:last-child):not(:first-child) .page-link {border: none;}
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

        <main>
            
            @include('admin.components.alerts.impersonator')

            @yield('content')

        </main>

        @include('layouts.footer')

        @include('auth.modal')
        
        @isset($popup)
        <div id="popup-container" {{isset($popupAlways) && $popupAlways ? 'always' : null}} data-view="{{$popup}}" data-url="{{route('subscriptions.modal')}}"></div>
        @endisset

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

        $(window).scroll(function() {
            let scrollTop = $(this).scrollTop();

            if (searchPos) {
                if (scrollTop > searchPos.top) {
                    mb = $search.outerHeight()+'px';
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
    let expiresAt = moment(record, 'x').add(2, 'days');
    let isExpired = moment().isSameOrAfter(expiresAt);

    if (record == null || isExpired) {
        console.log('Showing popup');
        setCookie(cookie, moment().format('x'), 2);
        return this;
    }

    console.log('Popup already shown '+recordedAt.fromNow());
    console.log('Will show again '+expiresAt.fromNow());
    return $();
};
    
$(document).ready(function() {
    loadPopup($('#popup-container:not([always])'), function($modal) {
        $modal.checkCookie().showAfter(3);
    });

    loadPopup($('#popup-container[always]'), function($modal) {
        $modal.modal('show');
    });
});

$('button[show="subscription-modal"]').on('click', function() {
    let $btn = $(this);

    $btn.disable();

    loadPopup($btn, function($modal) {
        $modal.modal('show');
        $btn.enable();
    });
});

function loadPopup($container, callback = null)
{
    if ($container.length) {
        let view = $container.data('view');

        if (view)
            console.log('Loading the ' + view + ' view');
    
        axios.get($container.data('url'), {params: {view: view}})
             .then(function(response) {
                $('body').append(response.data);

                if (callback)
                    callback($(response.data));
             })
             .catch(function(error) {
                console.log(error);
             });
    } else {
        console.log('No popup to show');
    }
}


    if (iOS())
        $('.free-trial-launch').attr('href', "{{config('app.stores.ios')}}");

    function iOS() {

      var iDevices = [
        'iPad Simulator',
        'iPhone Simulator',
        'iPod Simulator',
        'iPad',
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

    <script type="text/javascript">
    $(window).on('load', function() {
        var container = document.querySelector('.grid');
        if (container) {
            var grid = new Masonry( container, {
                itemSelector: '.grid-item',
            });

            grid.on( 'layoutComplete', function( gridInstance, laidOutItems ) {
                $('div[data-loading]').fadeOut('fast');
                container.style.opacity = 1;
                container.style.transform = 'translateY(0)';
            });

            grid.layout();
        }
    });
    </script>

    @stack('scripts')

</body>
</html>
