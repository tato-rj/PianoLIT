@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users',
    'description' => 'View detailed information about the users'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <form method="GET" action="{{route('admin.memberships.validate.all')}}">
            @csrf
            <button class="btn btn-sm btn-success"><i class="fas fa-clipboard-check mr-2"></i>Validate all subscriptions</button>
          </form>
        </div>
        <div>
          @include('admin.components.filters.users', ['filters' => []])
        </div>
      </div>
      <div class="col-12 mt-2" id="multi-select" style="display: none;">
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
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="users-table">
          <thead>
            <tr>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col">Date</th>
              <th class="border-0" scope="col">Name</th>
              <th class="border-0" scope="col">Origin</th>
              <th class="border-0" scope="col">Status</th>
              <th class="border-0" scope="col">Super User</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @each('admin.pages.users.row', $users, 'user')
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'user'])
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#users-table').DataTable({
    // 'responsive': true,
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [0, 5, 6] } ],
    });
} );
</script>
<script type="text/javascript">
$('input.status-toggle').on('change', function() {
  let $input = $(this);

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(response) {
      alert(response.status);
    }
  });
});
</script>
<script type="text/javascript">
$('#delete-modal').on('shown.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  $(this).find('form').attr('action', url);
});
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