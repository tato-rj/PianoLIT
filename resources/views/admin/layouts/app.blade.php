<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>PianoLIT | Admin</title>
        <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
        
        <script>
            window.app = <?php echo json_encode([
                'csrfToken' => csrf_token(),
                'url' => \Request::root(),
                'user' => auth()->guard('admin')->user()
            ]); ?>
        </script>
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

  <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>

  <script type="text/javascript">
    $('.alert .fa').on('click', function(){
      $(this).parent().parent().remove();
    });
  </script>

  <script type="text/javascript">
    $(function () {
      $('[data-toggle="popover"]').popover()
    })
  </script>
  @yield('scripts')
</body>
    
</html>
