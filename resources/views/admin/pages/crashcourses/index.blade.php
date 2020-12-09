@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
          <a href="{{route('admin.crashcourses.create')}}" class="btn btn-sm btn-default mr-2">
            <i class="fas fa-plus mr-2"></i>Create a new Crash Course
          </a>
        </div>
    </div>

    @datatable(['table' => 'crash_courses', 'columns' => ['Date', 'Title', 'Number of lessons', 'Active subscriptions', 'Published', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTable('#crash_courses-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'title', name: 'crash_courses.title', class: 'dataTables_main_column'},
  {data: 'lessons', orderable: false, searchable: false},
  {data: 'active_subscriptions', orderable: false, searchable: false},
  {data: 'published', orderable: false, searchable: false},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection