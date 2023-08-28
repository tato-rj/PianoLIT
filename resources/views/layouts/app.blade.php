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

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')

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

            @yield('content')

        </main>

        @include('layouts.footer')

        @include('auth.modal')
       
        @isset($popup)
        <div id="popup-container" {{istrue($popup['always'] ?? null, 'always')}}
            @isset($popup['product'])
                 data-product-class="{{get_class($popup['product'])}}" 
                 data-product-id="{{$popup['product']->id}}" 
             @endisset
             data-view="{{$popup['view']}}" 
             data-url="{{route('subscriptions.modal')}}"></div>
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
        setCookie(cookie, moment().format('x'), 2);
        return this;
    }

    console.log('Popup already shown '+recordedAt.fromNow());
    console.log('Will show again '+expiresAt.fromNow());
    return $();
};
    
$(document).ready(function() {
    let $popup = $('#popup-container');

    loadPopup($popup, function($modal) {
        if ($popup.is('[always]')) {
            $modal.showAfter(3);
        } else {
            $modal.checkCookie().showAfter(3);
        }
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
        let productClass = $container.data('product-class');
        let productId = $container.data('product-id');
   
        axios.get($container.data('url'), {params: {view: view, productClass: productClass, productId: productId}})
             .then(function(response) {
                $('body').append(response.data);

                if (callback)
                    callback($(response.data));
             })
             .catch(function(error) {
                console.log(error);
             });
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
