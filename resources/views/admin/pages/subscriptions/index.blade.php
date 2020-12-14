@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'envelope', 
      'title' => 'Subscriptions', 
      'subtitle' => 'Manage all email subscriptions.',
      'action' => ['label' => 'Add new emails', 'modal' => 'add-modal']
    ])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.create')
        </div>
        <div class="d-flex">
          <div class="mr-2">

            <form method="GET" action="{{route('admin.subscriptions.export')}}" target="_blank" id="export-form">
              @csrf
              <input type="hidden" name="type" value="txt">
              <input type="hidden" name="ids">
              <button type="submit" class="btn btn-light"><i class="fas fa-file-alt mr-2"></i>Export emails</button>
            </form>
          </div>
          <button class="btn btn-danger" id="delete-all-btn" style="display: none;" data-action="{{route('admin.subscriptions.destroy-many')}}" data-toggle="modal" data-target="#delete-modal">Delete all selected</button>
        </div>
      </div>
    </div>

    @datatable(['table' => 'subscriptions', 'columns' => ['checkbox', 'Date', 'Email', 'Origin', 'Form ID', '']])

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
  {data: 'form_id', name: 'subscriptions.form_id'},
  {data: 'action', orderable: false, searchable: false},
]).create();
</script>

<script type="text/javascript">
$('#check-all-datatable').change(function() {
  $('.check-datatable').prop('checked', $(this).is(':checked'));
  getIds();
});

$(document).on('change', '.check-datatable', function() {
  if ($(this).is(':checked'))
    findUser($(this).data('id'));
  
  getIds();
});

function getIds()
{
  let ids = $('.check-datatable:checked').map(function() {
    return $(this).attr('data-id');
  }).toArray();

  if (ids.length) {
    $('#delete-all-btn').show();
  } else {
    $('#delete-all-btn').hide();    
  }

  $('form#export-form input[name="ids"]').val(JSON.stringify(ids));  
}

$('#delete-all-btn').click(function() {
    removeSelectedIds();
    addSelectedIds();
});

function addSelectedIds()
{
  let ids = $('.check-datatable:checked').map(function() {
    return $(this).attr('data-id');
  }).toArray();

  let inputs = `<div id="selected-ids">`;

  for (i=0; i<ids.length; i++) {
    inputs += `<input type="hidden" name="ids[]" value="`+ids[i]+`">`; 
  }

  inputs += `</div>`;

  $('#delete-modal form').append(inputs);

  $('#delete-modal form').attr('action', $('#delete-all-btn').data('action'));
}

function findUser(id)
{
  let $table = $('#subscriptions-table');
  let $cell = $table.find('input[data-id="'+id+'"]').closest('td').next('td').next('td');
  $cell.find('span').remove();
  $cell.append('<span class="text-warning ml-2"><i>searching for user...</span>');

  axios.get("{{route('admin.subscriptions.find-user')}}", {params: {id: id}})
      .then(function(response) {
        $cell.find('span').remove();
        $cell.append(response.data);
      })
      .catch(function(error) {
          console.log(error);
      });
}

function removeSelectedIds()
{
  $('#delete-modal form').attr('action', null);
  $('#delete-modal form div#selected-ids').remove();
  console.log('Selected ids removed');
}

$('#delete-modal').on('hidden.bs.modal', function (e) {
  removeSelectedIds();
})
</script>
@endsection