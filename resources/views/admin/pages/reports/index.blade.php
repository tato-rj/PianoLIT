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

    @datatableRaw(['model' => 'reports', 'columns' => ['Date', 'Name', 'Emails', 'Delivered', 'Failed', 'Opened', 'Clicked', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
(new DataTableRaw({
	table: '#reports-table',
	options: {order: [[0, 'desc']]}
})).create();
</script>

<script type="text/javascript">
$('#check-all-datatable').on('change', function() {
    $(this).closest('table').find('.check-datatable').click();
});

$('.check-datatable').on('change', function() {
  $('button[data-target="#delete-modal"]').prop('disabled', ! $('.check-datatable:checked').length);

  addSelectedIds();
});

function addSelectedIds()
{
  let ids = $('.check-datatable:checked').map(function() {
    return $(this).attr('data-id');
  }).toArray();

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