<div class="row">
  <div class="col-lg-6 col-md-6 col-12 mb-4">
    @php($location = $user->location()->exists() ? [
          'I\'m from' => $user->location->fullLocation,
          'Coordinates' => $user->location->coordinates
        ] : [])
    @list([
      'content' => array_merge([
        'Name' => $user->full_name . $user->countryFlag,
        'Email' => $user->email_confirmed ? 
          $user->email . '<span class="text-green ml-1" title="Email confirmed on ' . $user->email_verified_at->toFormattedDateString() . '"><i class="fas fa-check-circle"></i></span>' :
          $user->email . '<span class="text-muted ml-1" title="Email not yet confirmed"><i class="fas fa-hourglass-half"></i></span>',
        'Origin' => $user->formattedOrigin,
        'Favorites' => $user->favorites_count . ' ' . str_plural('piece', $user->favorites_count),
        'Tutorial Requests' => $user->tutorialRequests()->count() . ' ' . str_plural('request', $user->tutorialRequests()->count()),
        'Logs' => 'App ' . (new \App\Log\LogFactory)->count($user->id, 'app') . ' | Web ' . (new \App\Log\LogFactory)->count($user->id, 'web') . ' | Webapp ' . (new \App\Log\LogFactory)->count($user->id, 'webapp'),
        'Signed up on' => $user->created_at->toFormattedDateString()
      ], $location)
    ])
  </div>
  <div class="col-lg-3 col-md-4 col-sm-8 col-9 mx-auto mb-4">
    <div class="shadow-sm rounded d-inline-block border w-100 mb-1">
      <div class="px-4 py-2 mb-4 bg-light text-center"><strong class="text-blue">{{$user->membership()->exists() ? class_basename($user->membership->source) : 'Status'}}</strong></div>
      <div class="text-center mb-3 pb-4 px-4 border-bottom">
        @include('admin.components.users.status.lg', ['elements' => $user->statusElements()])
      </div>
      <div class="px-4 pb-3 text-center text-nowrap">
        <span class="text-muted mr-2 text-truncate">Super status</span>@toggle(['toggle' => $user->super_user, 'route' => route('admin.users.super-status', $user->id), 'autoToggle' => true])
      </div>
    </div>
    <div class="text-center">
      <form method="POST" action="{{route('api.memberships.status')}}" target="_blank">
        <input type="hidden" name="user_id" value="{{$user->id}}">
        <button class="btn-raw text-blue">See Json response</button>
      </form>
    </div>
  </div>
</div>