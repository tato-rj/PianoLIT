@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'music', 'title' => 'Composers', 'subtitle' => 'Manage all the composers.'])
    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
          <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Add a new composer
          </button>
        </div>
        <div>
          <button type="button" data-toggle="modal" data-target="#famous-birthdays" class="btn btn-sm btn-warning">
            <i class="fas fa-birthday-cake mr-2"></i>Famous birthdays
          </button>
        </div>
      </div>
    </div>

    @datatable(['table' => 'composers', 'columns' => ['Name', 'Famous', 'Pieces count', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.pages.composers.birthdays')
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