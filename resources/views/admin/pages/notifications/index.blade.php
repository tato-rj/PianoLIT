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

    @datatable(['model' => 'notifications', 'columns' => ['Date', 'Notification', '']])

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

(new DataTable({table: '#notifications-table'})).create();

</script>
@endsection