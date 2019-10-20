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
          <input type="hidden" name="subscription_name" placeholder="Your name here">
          <input type="hidden" name="started_at" value="{{now()}}">
          <input type="email" required name="email" placeholder="Add a new email here" class="form-control mr-2">
          <button type="submit" class="btn btn-default">Subscribe</button>
        </form>
        @include('admin.components.feedback', ['field' => 'name'])
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
              @foreach(\App\Subscription::lists() as $list)
              <th class="border-0" scope="col">{{snake_str($list)}}</th>
              @endforeach
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($subscriptions as $subscription)
            <tr title="Subscribed at {{$subscription->created_at->format('g:i:s a')}}">
              <td>{{$subscription->created_at->toFormattedDateString()}}</td>
              <td>{{$subscription->email}}</td>
              @foreach(\App\Subscription::lists() as $list)
              <td>@include('admin.components.toggle.subscription', ['list' => $list])</td>
              @endforeach
              <td class="text-right">
                <a href="mailto:{{$subscription->email}}" target="_blank" class="text-muted mr-2"><i class="far fa-envelope align-middle"></i></a>
                <a href="#" data-name="{{$subscription->email}}" data-url="{{route('subscriptions.destroy', $subscription->email)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
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