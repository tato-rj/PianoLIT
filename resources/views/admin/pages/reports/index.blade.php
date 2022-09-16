@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'envelope', 'title' => 'Email Reports', 'subtitle' => 'See detailed reports from the email lists.'])

    @datatableRaw(['model' => 'reports', 'columns' => ['Date', 'Name', 'Emails', 'Delivered', 'Failed', 'Opened', 'Clicked', '']])

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTableRaw({
	table: '#reports-table',
	options: {order: [[0, 'desc']]}
})).create();
</script>

@endsection