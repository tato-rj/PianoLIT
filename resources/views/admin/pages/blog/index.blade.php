@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'newspaper', 
      'title' => 'Blog', 
      'subtitle' => 'Manage the all the blog posts.',
      'action' => ['label' => 'Create a new post', 'url' => route('admin.posts.create')]
    ])

    @datatable(['table' => 'blog', 'columns' => ['Date', 'Title', 'Reading Time', 'Published', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#blog-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'title', name: 'posts.title', class: 'dataTables_main_column'},
  {data: 'reading_time', name: 'posts.reading_time'},
  {data: 'published', name: 'posts.published_at'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection