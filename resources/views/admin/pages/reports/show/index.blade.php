@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">

<style type="text/css">
.percentage-number {
	position: relative;
}

.percentage-number > span {
	position: absolute;
	left: 100%;
	bottom: 4px;
	font-size: 40%;
}
</style>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Email Report',
    'description' => 'View report from the ' . $report->first()->name . ' list'])

	<div class="d-flex justify-content-between mb-4">
		<div class="d-flex">
			@include('admin.pages.reports.show.card', [
				'color' => 'green', 
				'type' => 'DELIVERED', 
				'percentage' => percentage($event->delivered_count, $event->emails_count), 
				'number' => $event->delivered_count])
			@include('admin.pages.reports.show.card', [
				'color' => 'warning', 
				'type' => 'OPENED', 
				'percentage' => percentage($event->opens_count, $event->emails_count), 
				'number' => $event->opens_count])
			@include('admin.pages.reports.show.card', [
				'color' => 'blue', 
				'type' => 'CLICKED', 
				'percentage' => percentage($event->clicks_count, $event->emails_count), 
				'number' => $event->clicks_count])
		</div>
		<div class="m-2">
			<a href="#" data-url="{{route('admin.subscriptions.reports.destroy', $event->list_id)}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="btn btn-outline-danger"><i class="fas fa-trash-alt mr-1"></i>Delete report</a>
		</div>
	</div>
	
    @datatable(['table' => 'report', 'columns' => ['Status', 'Recipient', 'Delivered at', 'Failed at', 'Opened', 'Clicked']])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTable('#report-table')).columns([
  {data: 'status'},
  {data: 'recipient', name: 'email_logs.recipient', class: 'dataTables_main_column'},
  {data: 'delivered_at'},
  {data: 'failed_at'},
  {data: 'opened'},
  {data: 'clicked'},
]).create();
</script>

@endsection