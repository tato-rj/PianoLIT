@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
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
          @include('admin.components.filters')
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
    'columnDefs': [ { 'orderable': false, 'targets': [0, 4] } ],

  });
} );
</script>
@endsection