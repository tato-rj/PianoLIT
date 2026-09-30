<!doctype html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('admin.layouts.html.theme')

    <title>{{local() ? '(local)' : null}} PianoLIT | Admin</title>

    
    <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
    
    @include('admin.layouts.html.js-app')
<style type="text/css">
/* Works on Firefox */
.navbar-nav {
  scrollbar-width: thin;
  scrollbar-color: rgba(0,0,0,0.1) transparent;
}

/* Works on Chrome, Edge, and Safari */
.navbar-nav::-webkit-scrollbar, .navbar-collapse::-webkit-scrollbar {
  width: 6px;
}

.navbar-nav::-webkit-scrollbar-track, .navbar-collapse::-webkit-scrollbar-track {
  background: transparent;
}

.navbar-nav::-webkit-scrollbar-thumb, .navbar-collapse::-webkit-scrollbar-thumb {
  background-color: rgba(0,0,0,0.1);
  border-radius: 20px;
}

  .navbar-sidenav a:hover {
    background-color: rgba(0,0,0,0.1)!important;
  }

.navbar-toggler {
  padding: 0!important;
}
@media (max-width:768px){
  #user-stats-overview > div:first-child {
      border-top-left-radius: var(--radius);
      border-bottom-left-radius: 0!important;
      border-top-right-radius: var(--radius);
    }

  #user-stats-overview > div:last-child {
      border-bottom-left-radius: var(--radius);
      border-top-right-radius: 0!important;
      border-bottom-right-radius: var(--radius);
  }
}

@media (max-width:992px){
  .navbar {
    background-color: #f8f9fa;
  }
  .nav-link {padding: .8rem!important}

  .navbar-nav .nav-item {border: 0!important;}

  #user-stats-overview > div:first-child {
      border-top-left-radius: var(--radius);
      border-bottom-left-radius: var(--radius);
    }

  #user-stats-overview > div:last-child {
      border-top-right-radius: var(--radius);
      border-bottom-right-radius: var(--radius);
  }
}

@media (min-width:992px) {
  .navbar {
    background-color: #636f83;
  }

  #user-stats-overview > div:first-child {
      border-top-left-radius: var(--radius);
      border-bottom-left-radius: var(--radius);
    }

  #user-stats-overview > div:last-child {
      border-top-right-radius: var(--radius);
      border-bottom-right-radius: var(--radius);
  }

  .navbar-brand {
    color: white!important;
  }

  .navbar-nav:not(.navbar-sidenav) .nav-link {
    color: white!important;
  }
}


</style>
    @yield('head')
  </head>

  <body class="fixed-nav sticky-footer" id="page-top">
    @include('admin.layouts.header.bar')

    <div class="px-2 py-3">
      @yield('content')
    </div>

    {{-- @include('admin.layouts.footer') --}}

    
    
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

    <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>

    <script type="text/javascript">
$('.editable-star').on('mouseover', function() {
  let $selection = $(this);

  resetSelections(false);
  $selection.addClass('icon-filled');
  $selection.prevAll('i').addClass('icon-filled');
});

$('.editable-star').on('mouseleave', function() {
  highlightSelected();
});

$('.editable-star').on('click', function() {
  let $selection = $(this);

  selectStars($selection);
  highlightSelected();
});

function selectStars($selection)
{
  resetSelections();
  $selection.attr('selected', true);
  $selection.prevAll('i').attr('selected', true);

  $('input[name="rating"]').val(selectedStars());
}

function highlightSelected()
{
  $('.editable-star').each(function() {
    if ($(this).attr('selected')) {
      $(this).addClass('icon-filled');
    } else {
      $(this).removeClass('icon-filled');
    }
  });
}

function selectedStars()
{
  return $('.editable-star[selected]').length;
}

function resetSelections(hard = true)
{
  if (hard) {
    $('.editable-star').removeAttr('selected').removeClass('icon-filled');
  } else {
    $('.editable-star').removeClass('icon-filled');
  }
}

$('#review-modal').on('hidden.bs.modal', function (e) {
  resetSelections();
  $('#review-modal input, #review-modal textarea').val('');
})
    </script>
    @yield('scripts')
  </body>
    
</html>
