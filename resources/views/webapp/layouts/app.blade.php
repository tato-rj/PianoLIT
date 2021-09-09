<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="HandheldFriendly" content="true" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:site_name" content="PianoLIT" />
    <meta property="og:title" content="PianoLIT WebApp" />
    <meta property="og:url" content="{{url()->current()}}" />
    <meta property="og:image" content="{{asset('images/webapp/thumbnail.jpg')}}" />

    @include('layouts.html.theme')

    <title>{{local() ? '(local)' : null}} {{config('app.name')}}{{isset($title) ? ' | ' . $title : null}}</title>

    <link rel="preload" href="{{ asset('css/vendor/fontawesome/all.min.css') }}" as="style">
    
    <link href="{{ asset('css/vendor/fontawesome/all.min.css') }}" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')
    <script type="text/javascript">
        window.urls = <?php echo json_encode([
            'search' => route('webapp.search.results'),
            'searchCount' => route('webapp.search.count', ['count'])
        ]); ?>
    </script>
    @stack('header')
</head>
<body>
    @include('layouts.html.google.manager-body')
   
    <div id="webapp" class="container">

        <div class="row">
            <div class="col-lg-8 col-md-12 mx-auto">

            <main>

                @include('admin.components.alerts.impersonator')

                @yield('content')

            </main>
            
            @include('webapp.layouts.footer')

            @include('webapp.layouts.menu')
            </div>

            @include('webapp.piece.share')
        </div>

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
    $('main').css('margin-bottom', $('#menu').height() + 20);
    </script>
    @stack('scripts')

</body>
</html>
