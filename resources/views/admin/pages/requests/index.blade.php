@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Tutorial Requests',
    'description' => 'Manage requests for tutorials'])

    <div class="row">
      <div class="col-12 mb-2">
        @env('local')
        <div>
        @include('admin.pages.requests.simulate')
        </div>
        @endenv
        <div>
        
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="requests-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Requested on</th>
              <th class="border-0" scope="col">Published on</th>
              <th class="border-0" scope="col">Piece</th>
              <th class="border-0" scope="col">User</th>
              <th class="border-0" scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            @each('admin.pages.requests.row', $requests, 'request')
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@component('components.overlays.modal', ['title' => 'Publish tutorial'])
<p class="m-0">Are you ready to publish this tutorial?</p>
<p class="mb-3"><u>The user will receive an email saying that the tutorial is ready.</u></p>
<form method="POST">
  @csrf
  @method('PATCH')
  <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, the tutorial is ready</button>
</form>
@endcomponent
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#requests-table').DataTable({
    // 'responsive': true,
    'aaSorting': [],
    });
} );
</script>
<script type="text/javascript">
$('#modal-publish-tutorial').on('shown.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  $(this).find('form').attr('action', url);
});
</script>
@endsection