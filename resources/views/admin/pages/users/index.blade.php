@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => 'Users', 'subtitle' => 'Use this page to view and manage the profile of our users.'])

    @component('admin.components.table-card')
    <div id="multi-select" style="display: none;">
      <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <div><strong><span id="selected-count">0</span> selected</strong></div>
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
    
    <div class="alert alert-danger" role="alert" data-users-table-error hidden>
      Users could not be loaded. Please try again.
      <button type="button" class="btn btn-sm btn-secondary ms-2" data-users-table-retry>Retry</button>
    </div>
    <div class="table-responsive">
      @datatable(['table' => 'users', 'standard' => true, 'ready' => true, 'initialBody' => view('admin.pages.users.table.rows', ['rows' => $initialUsers['data']]), 'columns' => ['checkbox', 'Date', 'ID', 'Name', 'Origin', 'Status', 'Super User', '']])
    </div>
    @endcomponent
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
var $usersTable = $('#users-table');
var $usersTableError = $('[data-users-table-error]');
function showUsersTable() {
  $usersTable.find('thead').removeClass('invisible');
  $usersTable.closest('.dataTables_wrapper').removeClass('table-loading');
}
$usersTable.on('xhr.dt', function(event, settings, json, xhr) {
  if (xhr !== settings.jqXHR) return true;
  if (json && Number(json.draw) < settings.iDraw) return true;
  $usersTableError.prop('hidden', !!json && !json.error);
  if (!json || json.error) {
    showUsersTable();
    return true;
  }
});
var usersTable = $usersTable.DataTable({
  processing: true,
  serverSide: true,
  deferLoading: {{ $initialUsers['recordsTotal'] }},
  autoWidth: false,
  pageLength: {{ \App\Support\AdminPagination::PER_PAGE }},
  lengthMenu: [{{ \App\Support\AdminPagination::PER_PAGE }}, 24, 48, 96],
  order: [[1, 'desc']],
  searchDelay: 350,
  ajax: {url: window.location.href, timeout: 15000},
  language: {
    processing: '<div class="overlay-pulse"></div>',
    paginate: {next: '&#8594;', previous: '&#8592;'}
  },
  columns: [
  {data: 'checkbox', orderable: false, searchable: false},
  {data: 'created_at', name: 'users.created_at', class: 'text-nowrap', sort: true},
  {data: 'id', name: 'users.id'},
  {data: 'name', name: 'users.first_name', class: 'dataTables_main_column'},
  {data: 'origin_display', name: 'users.origin', class: 'text-nowrap'},
  {data: 'status', class: 'text-nowrap', orderable: false, searchable: false},
  {data: 'super_user_display', name: 'users.super_user', class: 'text-nowrap', searchable: false},
  {data: 'actions', class: 'text-nowrap', orderable: false, searchable: false},
  ],
  initComplete: showUsersTable
});
$('[data-users-table-retry]').on('click', function() { usersTable.ajax.reload(null, false); });
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
