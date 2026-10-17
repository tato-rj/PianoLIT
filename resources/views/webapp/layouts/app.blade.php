@php($feedbackEnabled = request()->routeIs('webapp.*') && !request()->routeIs('webapp.tour', 'webapp.membership.checkout', 'webapp.membership.success'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @auth('web')
        @include('layouts.html.google.manager-head')
    @endauth
    @include('layouts.html.google.fonts')

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0{{ request()->routeIs('webapp.explore') ? ', shrink-to-fit=no' : '' }}">
    <meta name="HandheldFriendly" content="true" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:site_name" content="PianoLIT" />
    <meta property="og:title" content="PianoLIT WebApp" />
    <meta property="og:url" content="{{url()->current()}}" />
    <meta property="og:image" content="{{asset('images/webapp/thumbnail.jpg')}}" />

    @include('layouts.html.theme')

    <title>
        {{local() ? '(local)' : null}}
        @isset($title)
        {{$title}} | 
        @endisset
        {{config('app.name')}}
    </title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    @include('webapp.layouts.js-app')
    <script type="text/javascript">
        window.urls = <?php echo json_encode([
            'search' => route('webapp.search.results'),
            'searchCount' => route('webapp.search.count', ['count'])
        ]); ?>
    </script>
    @stack('header')

</head>
<body @if($feedbackEnabled) class="has-feedback-utility" @endif>
    @qrcode

    @auth('web')
        @include('layouts.html.google.manager-body')
    @endauth

    <div id="webapp" @if(request()->routeIs('webapp.playlists.show')) class="playlist-layout" @endif>
        @if(request()->routeIs('webapp.pieces.show'))
            @include('webapp.components.artwork-header', ['image' => asset($piece->web_image_background)])
        @elseif(request()->routeIs('webapp.playlists.show'))
            @include('webapp.components.artwork-header', ['image' => $playlist->cover_image ?: asset('images/webapp/collections/featured.webp')])
        @endif

        <div class="container">
            <div class="row">
                <div class="col-lg-10 col-md-12 mx-auto">

                <main>

                    @yield('content')

                </main>

                @include('webapp.layouts.footer')

                @include('webapp.layouts.menu')
                </div>

                @auth('web')
                    @include('webapp.piece.share')
                @endauth
            </div>
        </div>

        @stack('page-navigation')

        @if($message = session('status'))
        @alert([
            'color' => 'green',
            'message' => '<strong class="me-2">Success |  </strong>' . $message,
            'dismissible' => true,
            'floating' => 'top'])
        @endif

        @if($message = session('error') ?? $errors->first())
        @alert([
            'color' => 'red',
            'message' => '<strong class="me-2">Sorry |  </strong>' . $message,
            'dismissible' => true,
            'floating' => 'top'])
        @endif
    </div>

    @stack('page-modals')
    @include('webapp.piece.components.upgrade')
    @include('webapp.playlists.upgrade')
    @auth('web')
        @include('webapp.piece.components.saveto.offcanvas')
        @component('components.modal', ['id' => 'access-unavailable-modal', 'header' => 'Feature unavailable', 'centered' => true])
            @slot('body')<p class="mb-0">This feature is currently unavailable for your account. Your saved items are still here.</p>@endslot
        @endcomponent
    @endauth

    @guest('web')
        @include('webapp.components.signup-prompts')
    @endguest

    @include('components.popups.whatsnew', ['tabscount' => 1])

    @if(auth('web')->check() && !request()->routeIs('webapp.membership.checkout'))
        @include('components.checkout-host', ['checkoutUrl' => $checkoutUrl ?? null])
    @endif

    @include('webapp.components.campaign-attribution')

    @if($feedbackEnabled)
        @include('webapp.feedback.utility')
    @endif

    <script src="{{ mix('js/app.js') }}"></script>
    @include('components.activity')

    <script type="text/javascript">

    </script>
    <script type="text/javascript">
    $(document).ready(function() {
        let $optionsContainer = $('#options-container');

        if ($optionsContainer.length) {
            $(window).on('scroll', function() {
                let scrollTop = $(window).scrollTop();

                if ($optionsContainer.offset().top - scrollTop <= 0) {
                    $optionsContainer.addClass('border-bottom');
                } else {
                    $optionsContainer.removeClass('border-bottom');
                }
            });
        }
    });
    </script>
    <script type="text/javascript">
    // ADJUST THE BOTTOM MARGIN OF THE PAGE ACCORDING TO THE HEIGHT OF THE MENU ON THE WEBAPP
    $('main').css('margin-bottom', $('#menu').height() / 2);
    (function () {
        var menu = document.getElementById('menu');
        if (!menu) return;
        function syncMenuHeight() {
            document.documentElement.style.setProperty('--webapp-menu-height', menu.getBoundingClientRect().height + 'px');
        }
        syncMenuHeight();
        if (window.ResizeObserver) {
            new ResizeObserver(syncMenuHeight).observe(menu);
        } else {
            window.addEventListener('resize', syncMenuHeight);
        }
    }());
    </script>

    <script type="text/javascript">
// $(document).ready(function() {
//  let cookie = 'pianolit_whatsnew_10.22.2023';

//  if (! getCookie(cookie) || getCookie(cookie) == 'null') {
//      $('.modal.autoshow').modal('show');

//      setCookie(cookie, moment().format('x'), 365);
//  }
// });

// $(document).ready(function() {
//  let cookie = 'pianolit_new_feature_stage';

//  if (! getCookie(cookie) || getCookie(cookie) == 'null') {
//      let options = {
//          placement: 'bottom',
//          title: '🎁 New feature!',
//          trigger: 'manual'
//      };

//      $('[new-feature]').tooltip(options).tooltip('show');

//      setCookie(cookie, moment().format('x'), 365);
//  }
// });
</script>
    @stack('scripts')

</body>
</html>
