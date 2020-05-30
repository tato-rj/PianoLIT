<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.analytics')
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="HandheldFriendly" content="true" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.html.theme')

    <title>{{local() ? '(local)' : null}} {{config('app.name')}}</title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    @include('layouts.html.js-app')
    <script type="text/javascript">
        window.urls = <?php echo json_encode([
            'search' => route('webapp.search.results'),
            'searchCount' => route('webapp.search.count', ['count'])
        ]); ?>
    </script>
    <style type="text/css">
        #blog-content img, #blog-content iframe {
            max-width: 100%;
        }

        .rounded {
            border-radius: 1rem!important;
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

        #webapp .menu-link {
            text-decoration: none;
            color: #969ba0;
            transition: .2s;
        }

        #webapp .menu-link:hover, #webapp .menu-link.active {
            color: #0055fe !important;
        }

        #webapp .menu-link .menu-icon {
            font-size: 1.4em;
        }
    </style>

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

        </div>

        @include('components/alerts/http')
    </div>

    <script src="{{ mix('js/app.js') }}"></script>

    <script type="text/javascript">
    $(document).on('click', 'button[data-manage="favorite"]', function(event) {
        event.preventDefault();
        let $button = $(this);
        let $heart = $button.find('i');
        let $label = $(this).siblings('span.favorite-label');

        $button.disable().addClass('opacity-6');

        axios.post($button.attr('data-url-toggle'))
            .then(function(response) {
                $heart.toggleClass('fas far');
                $label.text(response.data);
            })
            .catch(function(error) {
                alert('Sorry, we couldn\'t update your favorite at this time.');
            })
            .then(function() {
                $button.enable().removeClass('opacity-6');
            });
    });
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

        $('#bottom-popup > div').css('bottom', $('#bottom-popup').siblings('.row').outerHeight() + 14);
    });
    </script>
    <script type="text/javascript">
    $('a.toggle-favorite span').click(function() {
        $(this).siblings('button').click();
    });
    </script>
    <script type="text/javascript">
    $('main').css('margin-bottom', $('#menu').height() + 20);
    </script>
    @stack('scripts')

</body>
</html>
