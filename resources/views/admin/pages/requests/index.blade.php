@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">

@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    
    @datatable(['table' => 'requests', 'columns' => ['Date requested', 'Date published', 'Piece', 'User', '']])

  </div>
</div>

@component('components.modal', ['id' => 'request-types-modal', 'headerNoborder' => true, 'footerNoBorder' => true, 'size' => 'sm'])
@slot('title')
Types requested
@endslot
@slot('body')
<div id="request-types-list">
  
</div>
@endslot
@endcomponent

@component('components.overlays.modal', ['title' => 'Publish tutorial'])
<p class="m-0">Are you ready to publish this tutorial?</p>
<p class="mb-3"><u>The user will receive an email saying that the tutorial is ready.</u></p>
<form method="POST">
  @csrf
  @method('PATCH')
  <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, the tutorial is ready</button>
</form>
@endcomponent
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">

(new DataTable('#requests-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'published_at', name: 'tutorial_requests.published_at', class: 'text-nowrap'},
  {data: 'piece.medium_name_with_composer', name: 'piece.name', class: 'dataTables_main_column'},
  {data: 'user'},
  {data: 'action', orderable: false, searchable: false},
]).create();

</script>
<script type="text/javascript">
$('#modal-publish-tutorial').on('shown.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  $(this).find('form').attr('action', url);
});
</script>

<script type="text/javascript">
$('#request-types-modal').on('show.bs.modal', function (e) {
 let types = $(e.relatedTarget).data('types');
 $('#request-types-list').html('');
 types.forEach(function(type, index) {
  $('#request-types-list').append('<p class="mb-1">'+type+'</p>');
 });
});
</script>
@endsection