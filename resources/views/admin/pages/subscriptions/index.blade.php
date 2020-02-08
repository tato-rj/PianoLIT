@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Subscriptions',
    'description' => 'Manage the subscription list'])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.create')
        </div>
        <div>
          <form method="GET" action="{{route('admin.subscriptions.export')}}" target="_blank" id="export-form">
            @csrf
            <input type="hidden" name="type" value="txt">
            <input type="hidden" name="ids">
            <button type="submit" class="btn btn-light"><i class="fas fa-file-alt mr-2"></i>Export emails</button>
          </form>
        </div>
      </div>
    </div>

    @datatable(['table' => 'subscriptions', 'columns' => ['checkbox', 'Date', 'Email', 'Origin', '']])

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
  {data: 'email', name: 'subscriptions.email', class: 'dataTables_main_column'},
  {data: 'origin_url', name: 'subscriptions.origin_url'},
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