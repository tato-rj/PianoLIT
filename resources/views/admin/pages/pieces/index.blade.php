@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">

</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Manage the pieces'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between">
        <div>
          <a href="{{route('admin.pieces.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Add a new piece
          </a>
        </div>
        <div>
          @include('admin.components.filters.pieces')
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="pieces-table">
          <thead>
            <tr>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col">Piece</th>
              <th class="border-0" scope="col">Tags</th>
              <th class="border-0" scope="col">Composer</th>
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

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$('.delete').on('click', function (e) {
  $piece = $(this);
  name = $piece.attr('data-name');
  url = $piece.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
});

$(document).ready( function () {
  $('#pieces-table').DataTable({
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [0, 4] } ],

  });
} );
</script>

<script type="text/javascript">
// $('html').click(function(e) {
//   let $element = $(e.target);
// console.log($element.hasClass('level-element'));
//   if (!$element.hasClass('level-element')) {
//     if($element.hasClass('badge-level')) {
//       $('.levels-select').hide();
//       $element.next('div').show();                    
//     } else {
//       $('.levels-select').hide();   
//     }
//   } else {
//     alert('outside');
//   }
// });

$(window).click(function() {
  $('.levels-select').hide(); 
});

$('.levels-select').on('click', function(event) {
  event.stopPropagation();
});

$('.badge-level').on('click', function(event) {
  event.stopPropagation();
  $('.levels-select').hide();
  $(this).next('div').show();
});

$('.input-level').on('change', function() {
  let $level = $(this);
  let $badge = $($level.attr('data-badge'));
  let url = $level.attr('data-url');
  let originalClass = $badge.attr('data-original-class');
  let oldLevel = $badge.attr('data-original-id');
  let newLevel = $level.val();
console.log($badge);
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

  })
  .fail(function(response) {
    alert('Something went wrong...');
  });
});

</script>
@endsection