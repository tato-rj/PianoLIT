@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Blog',
    'description' => 'Manage the blog posts'])

    <div class="row d-none d-sm-flex">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
          <a href="{{route('admin.posts.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Create a new post
          </a>
        </div>
        <div>
          @include('admin.components.filters.blog', ['filters' => []])
        </div>
      </div>
    </div>

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
  {data: 'title', class: 'dataTables_main_column'},
  {data: 'reading_time'},
  {data: 'published'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection