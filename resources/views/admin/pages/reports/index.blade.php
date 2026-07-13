@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'envelope', 
      'title' => 'Email Reports', 
      'subtitle' => 'See detailed reports from the email lists.'])
    
    <button class="btn btn-danger mb-4" id="delete-all-btn" disabled data-action="{{route('admin.subscriptions.reports.destroy-many')}}" data-toggle="modal" data-target="#delete-modal">Delete all selected</button>

    @datatable(['table' => 'reports', 'columns' => ['checkbox', 'Date', 'Name', 'Emails', 'Delivered', 'Failed', 'Opened', 'Clicked', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTable('#reports-table')).columns([
  {data: 'checkbox', orderable: false, searchable: false},
  {data: 'sent_at', searchable: false, sort: true},
  {data: 'name'},
  {data: 'emails_count', searchable: false},
  {data: 'delivered', searchable: false},
  {data: 'failed', searchable: false},
  {data: 'opened', searchable: false},
  {data: 'clicked', searchable: false},
  {data: 'actions', orderable: false, searchable: false},
]).create();
</script>

<script type="text/javascript">
let selectedReportIds = new Set;

$(document).on('change', '.check-datatable', function() {
  if ($(this).is(':checked')) {
    selectedReportIds.add($(this).attr('data-id'));
  } else {
    selectedReportIds.delete($(this).attr('data-id'));
  }

  addSelectedIds();
});

$(document).on('change', '#check-all-datatable', function() {
  $('.check-datatable').prop('checked', $(this).is(':checked')).trigger('change');
});

$('#reports-table').on('draw.dt', function() {
  $('.check-datatable').each(function() {
    $(this).prop('checked', selectedReportIds.has($(this).attr('data-id')));
  });
  $('#check-all-datatable').prop('checked', false);
});

function addSelectedIds()
{
  let ids = Array.from(selectedReportIds);

  $('button[data-target="#delete-modal"]').prop('disabled', ! ids.length);

  $('#delete-modal form #selected-ids').remove();

  let inputs = `<div id="selected-ids">`;

  for (i=0; i<ids.length; i++) {
    inputs += `<input type="hidden" name="ids[]" value="`+ids[i]+`">`; 
  }

  inputs += `</div>`;

  $('#delete-modal form').append(inputs);

  $('#delete-modal form').attr('action', $('#delete-all-btn').data('action'));
}
</script>

@endsection
