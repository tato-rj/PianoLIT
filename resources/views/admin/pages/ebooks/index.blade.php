@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'shopping-cart', 'title' => 'eBooks', 'subtitle' => 'Manage all the eBooks available on the website.'])

    <div class="mb-4">
      <a href="{{route('admin.ebooks.create')}}" class="btn btn-sm btn-default">
        <i class="fas fa-plus mr-2"></i>Create a new eBook
      </a>
    </div>

    @datatable(['table' => 'ebooks', 'columns' => ['Date', 'Title', '# Purchases', 'Published', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#ebooks-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'title', name: 'ebooks.title', class: 'dataTables_main_column'},
  {data: 'purchases_count', name: 'ebooks.purchases_count'},
  {data: 'published', name: 'ebooks.published_at'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection