<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" sizes="180x180" href="{{asset('admin-apple-touch-icon.png')}}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{asset('admin-favicon-32x32.png')}}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{asset('admin-favicon-16x16.png')}}">
        <link rel="manifest" href="{{asset('admin.site.webmanifest')}}">
        <link rel="mask-icon" href="{{asset('admin-safari-pinned-tab.svg')}}" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">

        <title>{{local() ? '(local)' : null}} PianoLIT | Admin</title>
        <link rel="stylesheet" type="text/css" href="{{asset('css/primer.css')}}">
        <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
        
        <script>
            window.app = <?php echo json_encode([
                'csrfToken' => csrf_token(),
                'url' => \Request::root(),
                'user' => auth()->guard('admin')->user(),
                'user_model' => get_class(auth()->guard('admin')->user()),
                'user_id' => auth()->guard('admin')->user()->id,
            ]); ?>
        </script>
        <style type="text/css">
          .notifications-count {
            width: 18px; 
            height: 18px; 
            font-size: .7em; 
            bottom: 0; 
            right: 0;
            display: none;
          }
          .notifications-link.active i {
            color: #38c172;
          }
          .notifications-link.active .notifications-count {
            display: flex !important;
          }
        </style>
        @yield('head')
    </head>
<body class="fixed-nav sticky-footer bg-dark" id="page-top">

  @include('admin/layouts/menu')

  @yield('content')

  @include('admin/layouts/footer')
  @include('admin/components/modals/logout')

  @if($message = session('status'))
    @include('components/alerts/success')
  @endif

  @if($message = session('error'))
    @include('components/alerts/error')
  @endif

  @if($errors->any())
    @include('components/alerts/error', ['message' => $errors->first()])
  @endif

  <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>

<script type="text/javascript">
  $('a[data-toggle="fixed-panel"]').on('click', function() {
    let $link = $(this);
    let $panel = $($link.attr('data-target'));

    $link.removeClass('active');

    $panel.fadeToggle();
    
    $('body').toggleCssBetween('overflow', ['hidden', 'auto']);
    
    $panel.find('.panel-content').css('right', 0);
  });

  $('button[data-dismiss="fixed-panel"], .fixed-panel .panel-overlay').on('click', function() {
    let $panel = $(this).closest('.fixed-panel');

    $panel.find('.panel-content').css('right', '-100%');

    $panel.fadeToggle();
    
    $('body').toggleCssBetween('overflow', ['hidden', 'auto']);
  });

  // $('.notification-item').on('click', function() {
  //   let notificationId = $(this).attr('data-id');
  //   let url = $(this).attr('data-url');
  //   let target = $(this).attr('data-target');

  //   $.get(url, function(data, status, xhr) {
  //     window.location.href = target;
  //   });
  // });
</script>

  <script type="text/javascript">
    setTimeout(function() {
      $('.alert-container').fadeOut(function() {
        $(this).remove();
      });
    }, 2000);
  </script>

  <script type="text/javascript">
    $('.alert .fa').on('click', function(){
      $(this).parent().parent().remove();
    });
  </script>

  <script type="text/javascript">
    $(function () {
      $('[data-toggle="popover"]').popover()
    })

    $('.modal').each(function() {
      if ($(this).find('.is-invalid')[0])
        $(this).modal('show');
    });
  </script>
  @yield('scripts')
  <script type="text/javascript">
    var audio = new Audio;
    $(document).on('click', '.play-clip', function() {
      let $icon = $(this).find('i');
      let src = $(this).attr('data-src');

      if (src) {
        $('.play-clip i').not($icon).removeClass('fa-stop-circle').addClass('fa-play-circle');
        stop();

        if ($icon.hasClass('fa-play-circle'))
          play(src);
        
        $icon.toggleClass('fa-play-circle fa-stop-circle');
      }
    });

    function stop() {
        audio.pause;
        audio.src = null;
    }

    function play(src) {
      audio.src = src;
      audio.play();
    }

    function isEmpty(obj) {
      for(var key in obj) {
        if(obj.hasOwnProperty(key))
          return false;
      }
      return true;
    }
  </script>
</body>
    
</html>
