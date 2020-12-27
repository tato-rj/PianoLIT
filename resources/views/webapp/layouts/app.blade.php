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

    <title>{{local() ? '(local)' : null}} {{config('app.name')}}</title>

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
    <style type="text/css">
    .piece-result:not(:last-of-type) {
        border-bottom: 1px solid #dee2e6 !important;
    }

.custom-scroll::-webkit-scrollbar {
    width: 6px;
}
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
<!-- Facebook Pixel Code -->
@end('production')
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '208256284230812');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=208256284230812&ev=PageView&noscript=1"
/></noscript>
@endenv
<!-- End Facebook Pixel Code -->
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
    $(document).on('click', '[data-dismiss=popup]', function() {
        $('#bottom-popup').fadeOut('fast');
    });

    $(document).on('click', 'button[data-manage="favorite"]', function(event) {
        event.preventDefault();
        let $button = $(this);
        let $heart = $button.find('i');
        let $label = $(this).siblings('span.favorite-label');

        $button.disable().addClass('opacity-6');

        axios.post($button.attr('data-url-toggle'))
            .then(function(response) {
                $heart.toggleClass('fas far');
                $label.text(response.data.status ? $label.data('label-true') : $label.data('label-false'));
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

    <script type="text/javascript">
    $(document).on('click', 'button[data-manage=save-to]', function() {
        let $btn = $(this);
        $btn.disable();

        axios.get($btn.data('url'))
            .then(function(response) {
                $('#bottom-popup-content').html(response.data)
                $('#bottom-popup-content > div').width($('main').width());
                $('#bottom-popup').show();
            })
            .catch(function(error) {
                $('#bottom-popup').fadeOut('fast');
            })
            .then(function() {
                $btn.enable();
            });
    });

    $(document).on('click', 'button[data-submit=favorite]', function() {
        let $btn = $(this);
        let $btns = $btn.parent().find('button');
        let $icons = $btn.find('.favorite-icons');
        $btns.disable();

        axios.post($btn.data('url'))
            .then(function(response) {
                showIcon($icons, response.data);
                updateFlag($btn.data('target'));
            })
            .catch(function(error) {
                alert('Something went wrong...');
                console.log(error);
            })
            .then(function() {
                $btns.enable();
            });
    });

    $(document).on('click', 'button[data-submit=folder]', function() {
        let $btn = $(this);
        let $name = $($btn.data('name'));
        let $container = $('#favorite-folders-container');

        $btn.siblings('button').hide();

        $btn.disable().text('Saving...');

        axios.post($btn.data('url'), {name: $($name).val()})
            .then(function(response) {
                $container.html(response.data.html.list);
            })
            .catch(function(error) {
                alert('Something went wrong...');
                console.log(error);
            })
            .then(function() {
                //
            });
    });

    function showIcon($container, data) {
        $container.find('i').hide();
        $container.find('i[name="success"]').fadeIn('fast');

        setTimeout(function() {
            $container.closest('#favorite-folders-container').html(data.html.list)
        }, 1000);
    }

    function updateFlag(flag) {
        $(flag).find('i').toggleClass('far fas');
    }

    $(document).on('click', 'button.new-folder', function() {
        $(this).hide();
        $($(this).data('target')).show();
    });

    $(document).on('click', 'button.cancel-new-folder', function() {
        $($(this).data('container')).hide();
        $($(this).data('target')).show();
    });
    </script>
    @stack('scripts')

</body>
</html>
