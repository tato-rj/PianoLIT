@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Users',
    'description' => "$user->first_name's profile"])

    <div class="text-center">
      <a href="{{route('piano-lit.api.user', $user->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>  
   
    @include('projects/pianolit/users/show/basic')

    <ul class="nav nav-tabs mb-2" id="user-menu" role="tablist">
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'profile' || ! request()->has('section') ? 'active show' : null}}" name="profile" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">Profile</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'subscription' ? 'active show' : null}}" name="subscription" id="subscription-tab" data-toggle="tab" href="#subscription" role="tab" aria-controls="subscription" aria-selected="false">Subscription</a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{request('section') == 'behavior' ? 'active show' : null}}" name="behavior" id="behavior-tab" data-toggle="tab" href="#behavior" role="tab" aria-controls="behavior" aria-selected="false">Behavior</a>
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
      @include('projects/pianolit/users/show/profile')

      @include('projects/pianolit/users/show/subscription/section')

      @include('projects/pianolit/users/show/behavior')

      @include('projects/pianolit/users/show/manage')

      @if(app()->environment() == 'local')
      @include('projects/pianolit/users/show/sandbox')
      @endif
  </div>
  </div>
</div>

@include('projects/pianolit/components/modals/delete', ['model' => 'user'])
@include('projects/pianolit/components/modals/trial', ['model' => 'user'])
@include('projects/pianolit/components/modals/favorites')
@include('projects/pianolit/components/modals/history')

@endsection

@section('scripts')
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
$('#subscription-history').on('shown.bs.modal', function (e) {
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

$('#subscription-history').on('hidden.bs.modal', function (e) {
  $(this).find('#history-data').html('').hide();
  $(this).find('#history-loading').show();
});
</script>
@endsection