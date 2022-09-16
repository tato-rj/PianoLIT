@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<style type="text/css">
.dataTable td {
  vertical-align: middle;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'star-half-alt', 'title' => 'Reviews', 'subtitle' => 'Manage the reviews of any product on the website.'])
    
    @datatable(['table' => 'reviews', 'columns' => ['Date', 'Product', 'Rating', 'User', 'Published', '']])
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">

(new DataTable('#reviews-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'reviewable', name: 'reviewable.title', class: 'dataTables_main_column'},
  {data: 'rating', orderable: false, searchable: false},
  {data: 'user', orderable: false, searchable: false},
  {data: 'published', orderable: false, searchable: false},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection