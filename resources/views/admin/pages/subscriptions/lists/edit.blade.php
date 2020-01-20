@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Email list',
    'description' => 'Manage the ' . $list->name . ' list'])
    
    @include('components.return', ['url' => route('admin.subscriptions.lists.index'), 'to' => 'email lists'])
    @datatable(['table' => 'subscriptions', 'columns' => ['checkbox', 'Date', 'Email', 'Origin', 'Status', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">

(new DataTable('#subscriptions-table')).columns([
  {data: 'checkbox', orderable: false, searchable: false},
  {data: 'created_at', class: 'text-nowrap', sort: true},
  {data: 'email', name: 'subscriptions.email'},
  {data: 'origin_url', name: 'subscriptions.origin_url'},
  {data: 'status', name: 'subscriptions.email_lists'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>

<script type="text/javascript">
$('#check-all-datatable').change(function() {
  $('.check-datatable').prop('checked', $(this).is(':checked'));
  getIds();
});

$('.check-datatable').on('change', function() {
  getIds();
});

function getIds()
{
  let ids = $('.check-datatable:checked').map(function() {
    return $(this).attr('data-id');
  }).toArray();
  console.log(ids);

  $('form#export-form input[name="ids"]').val(JSON.stringify(ids));  
}
</script>
@endsection