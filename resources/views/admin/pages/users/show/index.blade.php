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
    'description' => "$user->first_name's profile"])

    <div class="text-center">
      <a href="{{route('api.users.show', ['user_id' => $user->id])}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>  
   
    @include('admin.pages.users.show.basic')

    <ul class="nav nav-tabs mb-2" id="user-menu" role="tablist">
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'profile' || ! request()->has('section') ? 'active show' : null}}" name="profile" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">Profile</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'membership' ? 'active show' : null}}" name="membership" id="membership-tab" data-toggle="tab" href="#membership" role="tab" aria-controls="membership" aria-selected="false">Membership</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'behavior' ? 'active show' : null}}" name="behavior" id="behavior-tab" data-toggle="tab" href="#behavior" role="tab" aria-controls="behavior" aria-selected="false">Behavior</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'logs' ? 'active show' : null}}" name="logs" id="logs-tab" data-toggle="tab" href="#logs" role="tab" aria-controls="logs" aria-selected="false">Logs</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'manage' ? 'active show' : null}}" name="manage" id="manage-tab" data-toggle="tab" href="#manage" role="tab" aria-controls="manage" aria-selected="false">Manage</a>
      </li>
      @if(app()->environment() == 'local')
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'sandbox' ? 'active show' : null}}" name="sandbox" id="sandbox-tab" data-toggle="tab" href="#sandbox" role="tab" aria-controls="sandbox" aria-selected="false">Sandbox</a>
      </li>
      @endif
    </ul>

    <div class="tab-content">
      @include('admin.pages.users.show.profile')

      @include('admin.pages.users.show.membership.section')

      @include('admin.pages.users.show.behavior')

      @include('admin.pages.users.show.logs')

      @include('admin.pages.users.show.manage')

      @if(app()->environment() == 'local')
      @include('admin.pages.users.show.sandbox')
      @endif
  </div>
  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.components.modals.history')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable({
  table: '#log-app-table', 
  options: {pageLength: 5}
})).create();

(new DataTable({
  table: '#log-web-table', 
  options: {pageLength: 5}
})).create();
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
$('.piece').on('click', function() {
  $piece = $(this);
  $heart = $piece.find('.fa-heart');

  $heart.toggleClass('fas far');

  $.post($piece.attr('data-url'), {'piece_id': $piece.attr('data-id'), 'user_id': $piece.attr('data-user_id')}, 
    function(response){
      console.log(response);
    if(response.passes) {
      // console.log(response);
    } else {
      // console.log('We couldn\'t save your feedback right now.');
    }
  });
});
</script>
<script type="text/javascript">
// Explicitly save/update a url parameter using HTML5's replaceState().
function updateUrl(key, value) {
  baseUrl = [location.protocol, '//', location.host, location.pathname].join('');
  urlQueryString = document.location.search;
  var newParam = key + '=' + value,
  params = '?' + newParam;

  // If the "search" string exists, then build params from it
  if (urlQueryString) {
    keyRegex = new RegExp('([\?&])' + key + '[^&]*');
    // If param exists already, update it
    if (urlQueryString.match(keyRegex) !== null) {
      params = urlQueryString.replace(keyRegex, "$1" + newParam);
    } else { // Otherwise, add it to end of query string
      params = urlQueryString + '&' + newParam;
    }
  }
  window.history.replaceState({}, "", baseUrl + params);
}

$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
  var name = $(e.target).attr('name');
  updateUrl('section', name);
})
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
$('#delete-modal').on('shown.bs.modal', function(e) {
  let url = $(e.relatedTarget).attr('data-url');
  $(this).find('form').attr('action', url);
});
</script>
@endsection