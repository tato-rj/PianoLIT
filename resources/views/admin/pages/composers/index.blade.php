@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'music', 
      'title' => 'Composers', 
      'subtitle' => 'Manage all the composers.',
      'action' => ['label' => 'Add new composer', 'modal' => 'add-modal']
    ])

    @datatable(['table' => 'composers', 'columns' => ['Name', 'Famous', 'Pieces count', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.pages.composers.create')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");

$(bornIn).inputmask("99/99/9999");
$(diedIn).inputmask("99/99/9999");

(new DataTable('#composers-table')).columns([
  {data: 'name', name: 'composers.name', class: 'dataTables_main_column'},
  {data: 'famous', searchable: false},
  {data: 'pieces_count', searchable: false},
  {data: 'actions', orderable: false, searchable: false},
]).order('asc').create();
</script>
@endsection