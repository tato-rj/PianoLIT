@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'play-circle', 
      'title' => 'Video Clips', 
      'subtitle' => 'Manage video clips to be used anywhere.',
      'action' => ['label' => 'Create a new clip', 'modal' => 'add-modal']
    ])

    @datatable(['table' => 'clips', 'columns' => ['Name', 'URL', '']])

  </div>
</div>
@include('admin.components.modals/delete')
@include('admin.pages.clips.create')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.6/dist/clipboard.min.js"></script>
<script type="text/javascript">
var clipboard = new ClipboardJS('.c-clip');

clipboard.on('success', function(e) {
  let $target = $(e.trigger).find('>span');
  
  $target.tooltip('enable');
  $target.tooltip('show');

  setTimeout(function() {
    $target.tooltip('hide');
    $target.tooltip('disable');
  }, 1000);
});
</script>
<script type="text/javascript">
(new DataTable('#clips-table')).columns([
  {data: 'name'},
  {data: 'url', class: 'dataTables_main_column', name: 'clips.url', searchable: false},
  {data: 'actions', orderable: false, searchable: false},
]).create();
</script>
@endsection