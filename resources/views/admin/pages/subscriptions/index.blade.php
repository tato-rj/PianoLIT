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
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
        <form method="POST" action="{{route('subscriptions.store')}}" class="form-inline">
          @csrf
          <input type="email" required name="email" placeholder="Add a new email here" class="form-control mr-2">
          <button type="submit" class="btn btn-default">Subscribe</button>
        </form>
        @include('admin.components.feedback', ['field' => 'name'])
        </div>
        <div>
          {{-- @include('admin.components.filters', ['filters' => []]) --}}
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
              <th class="border-0" scope="col">Status</th>
              <th class="border-0" scope="col"></th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($subscriptions as $subscription)
            <tr>
              <td>{{$subscription->created_at->toFormattedDateString()}}</td>
              <td>{{$subscription->email}}</td>
              <td id="status-{{$subscription->id}}" class="status-text text-{{$subscription->is_active ? 'success' : 'warning'}}">{{ucfirst($subscription->status)}}</td>
              <td class="text-right">
                <a href="mailto:{{$subscription->email}}" target="_blank" class="text-muted mr-2"><i class="far fa-envelope align-middle"></i></a>
                <a href="#" data-name="{{$subscription->email}}" data-url="{{route('subscriptions.destroy', $subscription->email)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
              </td>
              <td class="text-right">
                @include('admin.components.toggle.subscription')
              </td>
            </tr>
            @endforeach
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
  let $label = $($input.attr('data-target'));

  $label.addClass('text-muted').removeClass('text-warning text-success');
  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      if ($input.is(':checked')) {
        $label.text('Subscribed').toggleClass('text-muted text-success');
      } else {
        $label.text('Unsubscribed').toggleClass('text-muted text-warning');
      }
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
    'order': [[0, 'desc']],
    });
} );
</script>
@endsection