@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['title' => 'Notifications', 'subtitle' => 'The latest updates from across PianoLIT.'])


    @datatable(['table' => 'notifications', 'columns' => ['Date', 'Notification', '']])

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#notifications-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'data.message'},
  {data: 'action', orderable: false, searchable: false},
]).create();

</script>
@endsection
