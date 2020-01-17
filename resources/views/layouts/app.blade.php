<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-137232556-1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-137232556-1');
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="p:domain_verify" content="0e0049943817b0a70c40da75d3783d3a"/>
    <meta name="apple-itunes-app" content="app-id={{config('appleId')}}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('favicon-16x16.png')}}">
    <link rel="manifest" href="{{asset('site.webmanifest')}}">
    <link rel="mask-icon" href="{{asset('safari-pinned-tab.svg')}}" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#1876f6">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{local() ? '(local)' : null}} {{$title ?? config('app.name')}}</title>

    @if(! empty($shareable))
        <meta name="keywords" content="{{$shareable['keywords']}}">
        <meta name="twitter:card" value="{{$shareable['description']}}">
        <meta property="og:site_name" content="PianoLIT" />
        <meta property="og:title" content="{{$shareable['title']}}" />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="{{url()->current()}}" />
        <meta property="og:image" content="{{$shareable['thumbnail']}}" />
        <meta property="og:image:width" content="400" />
        <meta property="og:image:height" content="245" />
        <meta property="og:description" content="{{$shareable['description']}}" />
        <meta property="article:published_time" content="{{$shareable['created_at']}}">
        <meta property="article:modified_time" content="{{$shareable['updated_at']}}">
        <meta property="og:updated_time" content="{{$shareable['updated_at']}}">

        <meta name="twitter:site" content="@litpiano">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:image" content="{{$shareable['thumbnail']}}">
        <meta name="twitter:title" content="{{$shareable['title']}}">
        <meta name="twitter:description" content="{{$shareable['description']}}">
        <meta name="twitter:app:country" content="US">
        <meta name="twitter:app:name:iphone" content="PianoLIT">
        <meta name="twitter:app:id:iphone" content="00000000">

        <meta itemprop="name" content="{{$shareable['title']}}"/>
        <meta itemprop="headline" content="{{$shareable['description']}}"/>
        <meta itemprop="description" content="{{$shareable['description']}}"/>
        <meta itemprop="image" content="{{$shareable['thumbnail']}}"/>
        <meta itemprop="datePublished" content="{{$shareable['created_at']}}"/>
        <meta itemprop="dateModified" content="{{$shareable['updated_at']}}" />
        <meta itemprop="author" content="PianoLIT"/>

        <link rel="canonical" href="{{url()->current()}}" />
    @endif

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,800" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/primer.css') }}" rel="stylesheet">

    <style type="text/css">
        h1 {
            font-size: 2.75rem;
        }

        h1,h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        body {
            letter-spacing: .025em;
        }

        .navbar-nav .nav-link {
            white-space: nowrap;
        }

        .navbar-light .navbar-toggler {
            border: 0;
        }

        .navbar-nav .show>.nav-link {
            color: rgba(0,0,0,.5) !important;
        }

        .navbar-nav .nav-link:hover {
            color: rgba(0,0,0,.7) !important;
        }

        .accent-bottom::after {
            content: '';
            display: block;
            background-color: #0055fe;
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

        .blog-post {
            font-size: 1.15em;
            letter-spacing: .025em;
        }

        .blog-post h1, .blog-post h2, .blog-post h3, .blog-post h4 {
            margin-bottom: 1.5rem;
        }

        .card:hover .card-overlay {
            opacity: .6 !important;
        }

        .breadcrumb {
            background-color: transparent;
            font-size: 80%;
        }

    </style>

    <script>
        window.app = <?php echo json_encode([
            'csrfToken' => csrf_token(),
            'url' => \Request::root(),
            'youtube_key' => env('YOUTUBE_V3_KEY'),
            'user' => null
        ]); ?>
    </script>

    @stack('header')
</head>
<body>
    @confirmed(false)
    @include('auth.alerts.unconfirmed')
    @endconfirmed
    
    <div id="app">
        @if($full ?? true)
        <header class="container">
            @include('layouts.menu')
        </header>
        @endif

        <main style="overflow-x: hidden" class="{{$full ? null: 'mt-5'}}">
            @yield('content')
            
            @if(session()->has('impersonator'))
            <div class="alert alert-warning fixed-bottom-right"><i class="fas fa-exclamation-triangle mr-2"></i>You are impersonating {{possessive(auth()->user()->first_name)}} account</div>
            @endif
        </main>
        @include('components.overlays.search.form')

        @include('auth.modal')
        
        @if($full ?? true)
        @include('layouts.footer')
        @endif

        @if($message = session('status'))
        @include('components/alerts/success')
        @endif

        @if($message = session('error') ?? $errors->first())
        @include('components/alerts/error')
        @endif
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
    <script src="https://jsconsole.com/js/remote.js?dd36d38d-81f0-4a4f-bc13-09886c925a8b"></script>
    <script type='application/javascript' src="{{asset('js/vendor/fastclick.js')}}"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $.get('https://www.googleapis.com/youtube/v3/channels', {
            part: 'contentDetails',
            id: 'UCOB67MpdySxyTCZHvWgxHeg',
            key: app.youtube_key},
            function(data, status, xhr) {
                $.each(data.items, function(key, item) {
                    getVideos(item.contentDetails.relatedPlaylists.uploads);
                });
            }
        );

        function getVideos(id) {

            $.get('https://www.googleapis.com/youtube/v3/playlistItems', {
                part: 'snippet',
                maxResults: 20,
                playlistId: id,
                key: app.youtube_key},
                function(data, status, xhr) {
                    let videos = getRandom(data.items, 2);
                    $('iframe.youtube-video-1').attr('src', 'https://www.youtube.com/embed/' + videos[0].snippet.resourceId.videoId);
                    $('iframe.youtube-video-2').attr('src', 'https://www.youtube.com/embed/' + videos[1].snippet.resourceId.videoId);

                    $('#youtube-previews').css('opacity', 1);
                }
            );
        }

        function getRandom(arr, n) {
            var result = new Array(n),
                len = arr.length,
                taken = new Array(len);
            if (n > len)
                throw new RangeError("getRandom: more elements taken than available");
            while (n--) {
                var x = Math.floor(Math.random() * len);
                result[n] = arr[x in taken ? taken[x] : x];
                taken[x] = --len in taken ? taken[len] : len;
            }
            return result;
        }
    });

    $(function() {
        FastClick.attach(document.body);
    });
    
    $(window).bind('load', function() {
        $('#load-screen').fadeOut(function() {
            $(this).remove();
        });
    });

    $('.btn-subscribe').on('click', function() {
        $("#subscribe-overlay").fadeIn('fast');
    });

    $('#tags-search .tag').on('click', function() {
      $tag = $(this);

      $tag.toggleClass('bg-light selected-tag');  
    });

    $('.show-overlay').on('click', function() {
        let overlayId = $(this).attr('data-target');
        $('body').css('overflow-y', 'hidden');
        $(overlayId).fadeIn();
    });

    $('.close-overlay').on('click', function() {
        let overlayId = $(this).attr('data-target');
        $('body').css('overflow-y', 'scroll');
        $(overlayId).fadeOut();
    }).children().on('click', function(e) {
        e.stopPropagation();
    });
    </script>

    <script type="text/javascript">
        $('input[name="search"]').on('keyup', function() {
            let $input = $(this);
            let $resulsContainer = $('#search-results');
            let $searching = $('#search-feedback > div');
            let url = $input.attr('data-url');

            if ($input.val().length > 2 && ! $searching.is(':visible')) {
                $searching.show();

                $.get(url, {'input': $input.val()}, function(data) {
                    $resulsContainer.html(data);
                    $searching.hide();
                });
            } else {
                $searching.hide();
                $resulsContainer.html('');
            }
        });
    </script>
    <script type="text/javascript">
    function percentage(piece, total) {
        return parseInt(piece * 100 / total);
    }

    let showScrollProgressBar = function(content) {
        let body = document.body,
            html = document.documentElement;

        let pageHeight = Math.max( body.scrollHeight, body.offsetHeight, 
                               html.clientHeight, html.scrollHeight, html.offsetHeight);

        let offset = content.offset().top;
        let height = content.height();

        let proportion = (height/pageHeight) + 1;

        let $progressbar = $('#page-progress .progress-bar');
        
        if ($progressbar.length) {
            $(window).scroll(function() {
                let scrollTop = $(this).scrollTop();
                $progressbar.css('width', percentage(scrollTop - offset*4, height)*proportion + '%');
            });
        }
    };
    </script>

<script type="text/javascript">
$('#auth-only').on('click', function(event) {
    event.preventDefault();
    $('#modal-auth').modal('show');
});

$('div[data-toggle="panel"]').on('click', function() {
    let target = $(this).attr('href');
    let parent = $(this).attr('href-parent');
    $(parent).add(target).toggle();
});  
</script>


<script language="JavaScript">
$('.no-click').bind('contextmenu', function(e) {
    return false;
}); 
</script>

    @stack('scripts')

</body>
</html>
