<!doctype html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('admin.layouts.html.theme')

    <title>{{local() ? '(local)' : null}} PianoLIT | Admin</title>


    <link rel="stylesheet" type="text/css" href="{{mix('css/admin.css')}}">
    @stack('admin-table-assets')

    @include('admin.layouts.html.js-app')

    @yield('head')
  </head>

  <body class="admin-shell @yield('body-class')" id="page-top">
    @include('admin.components.page-loading')
    <a href="#admin-main" class="admin-skip-link">Skip to content</a>
    @include('admin.layouts.header.bar')

    <main class="admin-main" id="admin-main" tabindex="-1">
      @yield('content')
    </main>

    <button class="admin-back-to-top" type="button" data-admin-back-to-top aria-label="Back to top" title="Back to top" hidden>
      @icon('arrow-up', ['mr' => 0])
    </button>

    {{-- @include('admin.layouts.footer') --}}



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

    <script type="text/javascript" src="{{mix('js/admin.js')}}"></script>
    <script src="{{mix('js/views/admin-notifications.js')}}"></script>

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
