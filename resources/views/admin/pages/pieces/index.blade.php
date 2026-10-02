@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<style type="text/css">
small .form-check-label::before, small .form-check-label::after {
    top: 0.10rem;
    left: -1.34rem;
}

</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'music',
      'title' => 'Pieces',
      'subtitle' =>
      'Manage all the pieces available on the app.',
      'action' => ['label' => 'Add a new piece', 'url' => route('admin.pieces.create')]
    ])

    <fieldset class="border rounded p-3 mb-3" data-piece-table-filters>
      <legend class="float-none w-auto px-2 fs-6">Filters</legend>
      <div class="d-flex flex-wrap gap-4">
        @foreach(['without_videos' => 'Pieces without video', 'without_moments' => 'Pieces without moments', 'without_synthesia' => 'Pieces without synthesia'] as $filter => $label)
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="filter-{{$filter}}" name="{{$filter}}" {{request()->boolean($filter) ? 'checked' : ''}}>
          <label class="form-check-label" for="filter-{{$filter}}">{{$label}}</label>
        </div>
        @endforeach
      </div>
      <p class="small text-muted mb-0 mt-2">Results match all checked filters. Uncheck all to show every piece.</p>
    </fieldset>
    <div class="alert alert-danger" role="alert" data-piece-table-error hidden>
      The pieces could not be loaded. Please try again.
      <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-piece-table-retry>Retry</button>
    </div>

    @datatable(['table' => 'pieces', 'columns' => ['ID', 'Piece', 'Composer', 'Tags', 'Level', 'Rankings', 'Favorited', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.pages.pieces.rankings', ['ranking' => 'abrsm'])
@include('admin.pages.pieces.rankings', ['ranking' => 'rcm'])

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script src="{{mix('js/views/admin-pieces.js')}}"></script>
<script type="text/javascript">
$('button#missing-image').on('click', function(e) {
  e.preventDefault();
  alert('This piece has no cover image.');
});

</script>

<script type="text/javascript">

$(window).click(function(e) {
  if(e.target.class == "input-tag")
    return;

  if($(e.target).closest('.tags-quick-edit').length)
    return;

  $('.popup').hide();
});

$('.popup').on('click', function(event) {
  event.stopPropagation();
});

$(document).on('click', '.badge-popup', function(event) {
  let $popup = $(this).next('div');
  let url = $popup.attr('data-url');
  event.stopPropagation();
  $('.popup').hide();
  $popup.show();

  if ($popup.find('.spinner').is(':visible')) {
    $.get(url, function(view) {
      $popup.find('.spinner').hide();
      $popup.find('.content').html(view);
    }).fail(function() {
      alert('We couldn\'t load the content...');
    });
  }
});

$(document).on('change', '.input-level', function() {
  let $level = $(this);

  if (! $level.is(':disabled')) {
    let $badge = $($level.attr('data-badge'));
    let url = $level.attr('data-url');
    let originalClass = $badge.attr('data-original-class');
    let oldLevel = $badge.attr('data-original-id');
    let newLevel = $level.val();

    $('.input-level').toggleAttr('disabled');

    $.ajax({
      url: url,
      type: 'PATCH',
      data: {old_level_id: oldLevel, new_level_id: newLevel}
    })
    .done(function(response) {
      $badge.removeClass(originalClass)
            .addClass('bg-'+response.level_name.toLowerCase())
            .text(response.level_name)
            .attr('data-original-id', response.level_id)
            .attr('data-original-class', 'bg-'+response.level_name.toLowerCase());

      $('.input-level').toggleAttr('disabled');
    })
    .fail(function(response) {
      alert('Something went wrong...');
    });
  }
});

$(document).on('change', '.input-tag', function() {
  let $tag = $(this);

  if (! $tag.is(':disabled')) {
    let $badge = $($tag.attr('data-badge'));
    let url = $tag.attr('data-url');
    let id = $tag.val();

    $tag.toggleAttr('disabled');

    $.ajax({
      url: url,
      type: 'PATCH',
      data: {id: id}
    })
    .done(function(response) {
      console.log(response.count);
      console.log($badge);
      $badge.text(response.count);
      $tag.toggleAttr('disabled');
    })
    .fail(function(response) {
      alert('Something went wrong...');
    });
  }
});

</script>
@endsection
