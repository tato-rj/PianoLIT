@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'graduation-cap', 'title' => 'Crash Course Subscriptions', 'subtitle' => 'Manage subscriptions from the crash courses.'])

    @datatable(['table' => 'crash_course_subscriptions', 'columns' => ['Date', 'First Name', 'Email', 'Crash Course', 'Status', '']])

  </div>
</div>

<div class="modal fade" id="confirm-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Confirm</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to <span id="form-action"></span>?
        <p class="text-danger"><small>This action cannot be undone</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST">
          @csrf
          <button type="submit" class="btn btn-sm btn-block btn-default">Yes, I am sure</button>
        </form>
      </div>
    </div>
  </div>
</div>
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