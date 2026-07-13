@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @component('admin.components.page.title', ['icon' => 'users', 'title' => 'Users', 'subtitle' => 'Use this page to view and manage the profile of our users.'])

      <form method="GET" disable-on-submit action="{{route('admin.memberships.validate.all')}}">
        @csrf
        <button class="btn btn-sm btn-success"><i class="fas fa-clipboard-check mr-2"></i>Validate all subscriptions</button>
      </form>

    @endcomponent

    <div id="multi-select" style="display: none;">
      <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <div><strong><span id="selected-count">3</span> selected</strong></div>
        <div>
          <form method="POST" action="{{route('admin.users.destroy-many')}}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="ids">
            <button type="submit" class="btn btn-sm btn-warning">Delete selected</button>
          </form>
        </div>
      </div>
    </div>
    
    @datatable(['table' => 'users', 'columns' => ['checkbox', 'Date', 'ID', 'Name', 'Origin', 'Status', 'Super User', '']])
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable('#users-table')).columns([
  {data: 'checkbox', orderable: false, searchable: false},
  {data: 'created_at', name: 'users.created_at', class: 'text-nowrap', sort: true},
  {data: 'id', name: 'users.id'},
  {data: 'name', name: 'users.first_name', class: 'dataTables_main_column'},
  {data: 'origin_display', name: 'users.origin'},
  {data: 'status', orderable: false, searchable: false},
  {data: 'super_user_display', name: 'users.super_user', searchable: false},
  {data: 'actions', orderable: false, searchable: false},
]).create();
</script>
<script type="text/javascript">
let selectedUserIds = new Set;

function updateUserSelection() {
  $('input[name="ids"]').val(JSON.stringify(Array.from(selectedUserIds)));
  $('#selected-count').text(selectedUserIds.size);
  $('#multi-select').toggle(selectedUserIds.size > 0);
}

$(document).on('change', '.check-datatable', function() {
  if ($(this).is(':checked')) {
    selectedUserIds.add($(this).attr('data-id'));
  } else {
    selectedUserIds.delete($(this).attr('data-id'));
  }

  updateUserSelection();
});

$('#check-all-datatable').on('change', function() {
  $('.check-datatable').prop('checked', $(this).is(':checked')).trigger('change');
});

$('#users-table').on('draw.dt', function() {
  $('.check-datatable').each(function() {
    $(this).prop('checked', selectedUserIds.has($(this).attr('data-id')));
  });
  $('#check-all-datatable').prop('checked', false);
});
</script>
@endsection
