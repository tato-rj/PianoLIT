@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'shopping-cart', 
      'title' => 'eBooks', 
      'subtitle' => 'Manage all the eBooks available on the website.',
      'action' => ['label' => 'Create a new eBook', 'url' => route('admin.ebooks.create')]
    ])

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