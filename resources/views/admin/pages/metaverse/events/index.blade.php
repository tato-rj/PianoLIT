@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'fire', 
      'title' => 'Metaverse', 
      'subtitle' => 'Manage all the events on the metaverse.',
      'action' => ['label' => 'Add a new event', 'modal' => 'add-modal']
    ])

    @datatable(['table' => 'metaverse', 'columns' => ['Date', 'Location', 'Theme', 'Time', 'Duration','']])

  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.pages.metaverse.events.create')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTable('#metaverse-table')).columns([
  {data: 'date'},
  {data: 'location'},
  {data: 'theme', class: 'dataTables_main_column'},
  {data: 'time'},
  {data: 'duration'},
  {data: 'actions', orderable: false, searchable: false},
]).order('asc').create();
</script>
@endsection