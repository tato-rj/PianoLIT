@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'music', 
      'title' => 'Pianists', 
      'subtitle' => 'Manage all the pianists highlighted on the website.',
      'action' => ['label' => 'Add a new pianist', 'modal' => 'add-modal']
    ])

    @datatable(['table' => 'pianists', 'columns' => ['Name', 'Nationality', '']])

  </div>
</div>

@include('admin.components.modals/delete')
@include('admin.pages.pianists.create')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");
$(bornIn).inputmask("99/99/9999");
$(diedIn).inputmask("99/99/9999");

(new DataTable('#pianists-table')).columns([
  {data: 'name', name: 'pianists.name', class: 'dataTables_main_column'},
  {data: 'nationality', searchable: false},
  {data: 'actions', orderable: false, searchable: false},
]).order('asc').create();
</script>
@endsection