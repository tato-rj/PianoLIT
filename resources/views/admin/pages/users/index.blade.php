@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
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
    
    @datatableRaw(['model' => 'users', 'columns' => ['checkbox', 'Date', 'ID', 'Name', 'Origin', 'Status', 'Super User', '']])
    {{-- @datatable(['table' => 'users', 'columns' => ['Date', 'ID', 'Name']]) --}}
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTableRaw({table: '#users-table', dontSortFirst: true, options: {order: [[1, 'desc']]}})).create();
// (new DataTable('#users-table')).columns([
//   {data: 'created_at', class: 'text-nowrap', sort: false},
//   {data: 'id', name: 'users.id'},
//   {data: 'first_name', name: 'users.first_name'},
// ]).create();
</script>
<script type="text/javascript">
$('.check-user').on('change', function() {
  let $selected = $('.check-user:checked');
  let $container = $('#multi-select');
  let ids = [];

  $selected.each(function() {
    ids.push($(this).attr('data-id'));
  });

  $('input[name="ids"]').val(JSON.stringify(ids));

  if ($selected.length > 0) {
    $container.find('#selected-count').text($selected.length);
    $container.show();
  } else {
    $container.hide();
  }
});
</script>
@endsection