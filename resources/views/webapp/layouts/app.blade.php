<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.html.google.analytics')
    @include('layouts.html.google.manager-head')
    @include('layouts.html.google.fonts')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

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

    @confirmed(false)
    @include('auth.alerts.unconfirmed')
    @endconfirmed
    
    <div id="webapp" class="container">

        <div class="row">
            <div class="col-lg-8 col-md-10 col-12 mx-auto">

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

        $button.disable().addClass('opacity-6');

        axios.post($button.attr('data-url-toggle'))
            .then(function(response) {
                $heart.toggleClass('fas far');
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
    $('main').css('margin-bottom', $('#menu').height() + 20);
    </script>
    @stack('scripts')

</body>
</html>
