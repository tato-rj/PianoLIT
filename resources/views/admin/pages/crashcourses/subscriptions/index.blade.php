@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    @datatable(['table' => 'crash_course_subscriptions', 'columns' => ['Date', 'First Name', 'Email', 'Crash Course', 'Status', '']])

  </div>
</div>

@include('components.modals.confirm')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTable('#crash_course_subscriptions-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'first_name'},
  {data: 'email'},
  {data: 'crash_course_title', class: 'dataTables_main_column'},
  {data: 'status', orderable: false, searchable: false},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection