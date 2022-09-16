@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container">

    @datatable(['table' => 'favorites', 'columns' => ['Date', 'Name', 'User', '# Pieces', '']])
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#favorites-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'name', name: 'name', class: 'dataTables_main_column'},
  {data: 'user', orderable: true, searchable: false},
  {data: 'favorites', name: 'favorites_count'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection
