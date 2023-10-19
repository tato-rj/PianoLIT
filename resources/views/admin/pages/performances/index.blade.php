@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<style type="text/css">
small .custom-control-label::before, small .custom-control-label::after {
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
      'title' => 'Recordings', 
      'subtitle' => 'Manage all the recordings submitted by the users.',
      'action' => ['label' => 'Video Uploader', 'external_link' => env('FILEMANAGER_URL')]
    ])

    @datatable(['table' => 'performances', 'columns' => ['ID', 'Piece', 'Composer', '# Claps', 'User', 'Approved', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#performances-table')).columns([
  {data: 'id', name: 'performances.id'},
  {data: 'piece', class: 'dataTables_main_column'},
  {data: 'composer', class: 'text-nowrap'},
  {data: 'claps_sum', name: 'claps_sum'},
  {data: 'user', class: 'text-nowrap'},
  {data: 'approved', class: 'text-nowrap'},
  {data: 'actions', orderable: false, searchable: false},
]).create();
</script>
@endsection