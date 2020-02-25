@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users',
    'description' => "$user->first_name's profile"])

    <div class="text-center">
      <a href="{{route('api.users.show', ['user_id' => $user->id])}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>  

    @include('admin.pages.users.show.profile')

    @include('admin.pages.users.show.logs')

    @include('admin.pages.users.show.membership')

    @include('admin.pages.users.show.manage')

    @if(app()->environment() == 'local')
    @include('admin.pages.users.show.sandbox')
    @endif
  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.components.modals.history')

@endsection

@section('scripts')
<script type="text/javascript">
$('.load-more').on('click', function() {
  let $button = $(this);
  let type = $button.attr('data-type');
  let limit = $button.closest('tr').siblings().length;

  $.get("{{route('admin.users.load-logs', $user->id)}}", {start_at: limit, type: type}, function(data) {
    if (data) {
      $(data).insertBefore($button.closest('tr'));
    } else {
      $button.prop('disabled', true).text('NO MORE LOGS');
    }
  });
});
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
$('.sandbox-button').on('click', function() {
  $(this).closest('form').submit();
});
</script>

<script type="text/javascript">
$('#membership-history').on('shown.bs.modal', function (e) {
  $user_id = $(this).attr('data-user_id');
  $url = $(this).attr('data-url');
  $loading = $(this).find('#history-loading');
  $container = $(this).find('#history-data');

  $.post($url, {user_id: $user_id},
  function(data, status){
    console.log(data);
    $loading.hide();
    $container.html(data).fadeIn();
  });
});

$('#membership-history').on('hidden.bs.modal', function (e) {
  $(this).find('#history-data').html('').hide();
  $(this).find('#history-loading').show();
});
</script>
<script type="text/javascript">
$('#modal-log-data').on('show.bs.modal', function (e) {
  let $container = $('#modal-log-data #data-container');
  let data = $(e.relatedTarget).attr('data');
  let url = $(e.relatedTarget).attr('data-url');

  $container.html('<h5 class="text-grey text-center py-4">Loading...</h5>');

  $.get("{{route('admin.log.data')}}", {data: data, url: url}, function(data, status) {
    $container.html(data);
  });
});
</script>
@endsection