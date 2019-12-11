@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Notifications',
    'description' => 'Manage the notifications'])

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="blog-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Date</th>
              <th class="border-0" scope="col">Notification</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach(auth()->user()->notifications as $notification)
              <tr class="{{($notification->read_at) ? 'opacity-4' : null}}">
                <td style="white-space: nowrap; vertical-align: inherit;">{{$notification->created_at->toFormattedDateString()}}</td>
                <td style="vertical-align: inherit;" title="This happened at {{$notification->created_at->format('g:i:s a')}}">{!! $notification->data['message'] !!}</td>
                <td class="text-right" style="white-space: nowrap;">
                  <button class="btn btn-sm btn-outline-secondary m-0 mark-notification" style="display: {{$notification->read_at ? 'inline-block' : 'none'}}" 
                    data-url="{{route('admin.notifications.unread', ['ids' => [$notification->id]])}}">Mark as unread</button>

                  <button class="btn btn-sm btn-outline-success m-0 mark-notification" style="display: {{$notification->read_at ? 'none' : 'inline-block'}}" 
                    data-url="{{route('admin.notifications.read', ['ids' => [$notification->id]])}}">Mark as read</button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$('button.mark-notification').on('click', function() {
  let $button = $(this);
  let $container = $button.parent().parent();

console.log($button.attr('data-url'));
  $.ajax({
    url: $button.attr('data-url'),
    type: 'GET',
    success: function(res) {
      $button.siblings('button').toggle();
      $button.toggle();
      $container.toggleClass('opacity-4');
    },
    error: function(xhr,status,error) {
      alert('Something went wrong: ' + error);
    }
  });
});

$(document).ready( function () {
    $('#blog-table').DataTable({
    'ordering': false,
    });
} );
</script>
@endsection