@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">

    @datatable(['table' => 'notifications', 'columns' => ['Date', 'Notification', '']])

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).on('click', 'button.mark-notification', function() {
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

(new DataTable('#notifications-table')).columns([
  {data: 'created_at', class: 'text-nowrap'},
  {data: 'data.message'},
  {data: 'action', orderable: false, searchable: false},
]).create();

</script>
@endsection