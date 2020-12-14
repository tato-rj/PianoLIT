@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'shopping-cart', 
      'title' => 'eScores', 
      'subtitle' => 'Manage all the eScores available on the website.',
      'action' => ['label' => 'Create a new eScore', 'url' => route('admin.escores.create')]
    ])

    @datatable(['table' => 'escores', 'columns' => ['Date', 'Title', '# Purchases', 'Published', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#escores-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'title', name: 'escores.title', class: 'dataTables_main_column'},
  {data: 'purchases_count', name: 'escores.purchases_count'},
  {data: 'published', name: 'escores.published_at'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection