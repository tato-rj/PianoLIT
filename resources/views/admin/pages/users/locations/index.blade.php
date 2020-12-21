@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => 'Locations', 'subtitle' => 'See where our users are from.'])

    @datatable(['table' => 'locations', 'columns' => ['Country', 'Region', 'City', 'User', '']])
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#locations-table')).columns([
  {data: 'countryName', class: 'text-nowrap'},
  {data: 'regionName', class: 'text-nowrap'},
  {data: 'cityName', class: 'text-nowrap'},
  {data: 'user', class: 'text-nowrap'},
  {data: 'action', orderable: false, searchable: false},
]).order('asc').create();
</script>
@endsection
