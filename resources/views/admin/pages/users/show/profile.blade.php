@include('admin.pages.users.show.title', ['title' => 'Basic Information', 'icon' => 'address-card'])

<div class="row">
  <div class="col-lg-6 col-md-6 col-12 mb-4">
    @list([
      'content' => [
        'Name' => $user->full_name,
        'Email' => $user->email_confirmed ? 
          $user->email . '<span class="text-green ml-1" title="Email confirmed on ' . $user->email_verified_at->toFormattedDateString() . '"><i class="fas fa-check-circle"></i></span>' :
          $user->email . '<span class="text-muted ml-1" title="Email not yet confirmed"><i class="fas fa-hourglass-half"></i></span>',
        'Gender' => ucfirst($user->gender),
        'Origin' => $user->formattedOrigin,
        'Favorites' => $user->favorites_count . ' ' . str_plural('piece', $user->favorites_count),
        'Logs' => 'App ' . (new \App\Log\LogFactory)->count($user->id, 'app') . ' | Web ' . (new \App\Log\LogFactory)->count($user->id, 'web'),
        'Member since' => $user->created_at->toFormattedDateString()
      ]
    ])
  </div>
  <div class="col-lg-3 col-md-4 col-sm-8 col-9 mx-auto mb-4">
    <div class="shadow-sm rounded d-inline-block border w-100">
      <div class="px-4 py-2 mb-4 bg-light text-center"><strong class="text-blue">Status</strong></div>
      <div class="text-center mb-3 pb-4 px-4 border-bottom">
        @include('admin.components.users.status.lg', ['elements' => $user->statusElements()])
      </div>
      <div class="px-4 pb-3 text-center text-nowrap">
        <span class="text-muted mr-2 text-truncate">Super status</span>@toggle(['toggle' => $user->super_user, 'route' => route('admin.users.super-status', $user->id)])
      </div>
    </div>
  </div>
</div>