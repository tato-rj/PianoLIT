@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
<style type="text/css">
.gift:hover img {
  display: block !important;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'question-circle', 
      'title' => 'Quizzes', 'subtitle' => 'Manage the all the quizzes.',
      'action' => ['label' => 'Create a new quiz', 'url' => route('admin.quizzes.create')]
    ])

    @datatable(['table' => 'quizzes', 'columns' => ['Date', 'Title', 'Number of questions', 'Published', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#quizzes-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'title', name: 'quizzes.title', class: 'dataTables_main_column'},
  {data: 'questions', orderable: false, searchable: false},
  {data: 'published', orderable: false, searchable: false},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>
@endsection