@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">
small .custom-control-label::before, small .custom-control-label::after {
    top: 0.10rem;
    left: -1.34rem;
}
.dropdown-toggle:after {
  display: none!important;
}
.dataTables_wrapper > .row:nth-of-type(2) {
  overflow-x: auto;
}
div.dataTables_paginate li.previous a:before, div.dataTables_paginate li.next a:after {
  content: none;
}
div.dataTables_paginate li.previous > a, div.dataTables_paginate li.next > a {
  padding: .5rem .75rem!important;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Manage the pieces'])

    <div class="row">
      <div class="col-lg-4 col-md-4 col-12 mb-3 hide-on-sm">
        <a href="{{route('admin.pieces.create')}}" class="btn btn-sm btn-default">
          <i class="fas fa-plus mr-2"></i>Add a new piece
        </a>
      </div>
      <div class="col-lg-8 col-md-8 col-12 mb-3">
        @include('admin.components.filters.pieces')
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-12">
        <table class="table table-hover" id="pieces-table" style="display: none;">
          <thead>
            <tr>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col">Piece</th>
              <th class="border-0" scope="col">Composer</th>
              <th class="border-0" scope="col">Rankings</th>
              <th class="border-0" scope="col">Tags</th>
              <th class="border-0" scope="col">Level</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($pieces as $piece)
            @include('admin.pages.pieces.row')
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'piece'])

@include('admin.pages.pieces.rankings', ['ranking' => 'abrsm'])
@include('admin.pages.pieces.rankings', ['ranking' => 'rcm'])
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$('button#missing-image').on('click', function(e) {
  e.preventDefault();
  alert('This piece has no cover image.');
});

$('.delete').on('click', function (e) {
  $piece = $(this);
  name = $piece.attr('data-name');
  url = $piece.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
});

$(document).ready( function () {
  $('#pieces-table').DataTable({
    aaSorting: [],
    ordering: false,
    language: {
      paginate: {
        next: '&#8594;', // or '→'
        previous: '&#8592;' // or '←' 
      }
    }
    // 'columnDefs': [ { 'orderable': false, 'targets': [0, 4] } ],
  });

  $('#pieces-table').fadeIn();
});
</script>

<script type="text/javascript">

$(window).click(function() {
  $('.popup').hide(); 
});

$('.popup').on('click', function(event) {
  event.stopPropagation();
});

$('.badge-popup').on('click', function(event) {
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