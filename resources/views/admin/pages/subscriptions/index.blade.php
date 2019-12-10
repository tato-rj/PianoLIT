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
      <div class="col-12 mb-2">
        <div class="mb-4">
        @include('admin.pages.subscriptions.create')
        </div>
        <div>
          <a href="{{route('admin.subscriptions.export', ['type' => 'txt'])}}" target="_blank" class="btn btn-light" name="export-list" data-type="text"><i class="fas fa-file-alt"></i></a>
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="blog-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Date</th>
              <th class="border-0" scope="col">Email</th>
              <th class="border-0" scope="col">Origin</th>
              @foreach(\App\Subscription::lists() as $list)
              <th class="border-0" scope="col">{{snake_str($list)}}</th>
              @endforeach
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @each('admin.pages.subscriptions.row', $subscriptions, 'subscription')
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'subscription'])

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$('input.status-toggle').on('change', function() {
  let $input = $(this);

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      alert('Your update was successful!');
    },
    error: function(xhr,status,error) {
      alert('Something went wrong: ' + error);
    }
  });
});

$('.delete').on('click', function (e) {
  $post = $(this);
  name = $post.attr('data-name');
  url = $post.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
});

$(document).ready( function () {
    $('#blog-table').DataTable({
    'ordering': false,
    });
} );
</script>
@endsection