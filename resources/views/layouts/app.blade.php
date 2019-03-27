<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,800" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    <style type="text/css">
        h1,h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .navbar-light .navbar-toggler {
            border: 0;
        }

        .navbar-brand h2 {
            font-weight: 800;
        }

        .accent-bottom::after {
            content: '';
            display: block;
            background-color: #1876f6;
            width: 100px;
            border-radius: 2px;
            height: 7px;
            margin-top: 1.5rem;
        }

        .font-serif {
            font-family: Georgia,Cambria,"Times New Roman",Times,serif;
            font-weight: normal;
            letter-spacing: .003em;
        }

        .font-lg {
            font-size: 1.15em;
            letter-spacing: .0175em;
        }

    </style>

    @stack('header')
</head>
<body>
    <div id="app">
        <header class="container">
            @include('layouts.menu')
        </header>
        <main style="overflow-x: hidden">
            @yield('content')
        </main>
        @include('components.search.overlay')
        @include('layouts.footer')
    </div>

    <script src="{{ mix('js/app.js') }}"></script>

    <script type="text/javascript">
    $('#tags-search .tag').on('click', function() {
      $tag = $(this);

      $tag.toggleClass('bg-light selected-tag');  
    });

    $('.show-overlay').on('click', function() {
        let overlayId = $(this).attr('data-target');

        $(overlayId).fadeIn();
    });

    $('.close-overlay').on('click', function() {
        let overlayId = $(this).attr('data-target');

        $(overlayId).fadeOut();
    });
    </script>

    <script type="text/javascript">
        $('input[name="search"]').on('keyup', function() {
            let $input = $(this);
            let $resulsContainer = $('#search-results');
            let $searching = $('#search-feedback div:first');
            let $feedback = $('#search-feedback div:last');
            let url = $input.attr('data-url');

            if ($input.val().length > 2 && ! $searching.is(':visible')) {
                $searching.show();
                $feedback.hide();

                $.get(url, {'input': $input.val()}, function(data) {
                    $resulsContainer.html(data);
                    $searching.hide();
                    $feedback.text('We found ' + $resulsContainer.length + ' result(s)').show();
                });
            }
        });
    </script>
    @stack('scripts')

</body>
</html>
