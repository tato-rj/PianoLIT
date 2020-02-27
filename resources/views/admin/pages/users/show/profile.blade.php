@component('admin.pages.users.show.title', ['title' => 'Basic Information'])
<span class="text-muted mr-3">Super user status</span>@toggle(['toggle' => $user->super_user, 'route' => route('admin.users.super-status', $user->id)])
@endcomponent

<div class="row">
  <div class="col-12">
    @include('components.table', [
      'content' => [
        'Name' => $user->full_name,
        'Email' => $user->email_confirmed ? 
          $user->email . ' <span class="badge alert-green">confirmed on ' . $user->email_verified_at->toFormattedDateString() . '</span>' :
          $user->email . ' <span class="badge badge-light">not yet confirmed</span>',
        'Gender' => ucfirst($user->gender),
        'Origin' => $user->formattedOrigin,
        'Status' => $user->membership_status,
        'Favorites' => $user->favorites_count . ' ' . str_plural('piece', $user->favorites_count),
        'Logs' => 'App ' . (new \App\Log\LogFactory)->count($user->id, 'app') . ' | Web ' . (new \App\Log\LogFactory)->count($user->id, 'web'),
        'Member since' => $user->created_at->toFormattedDateString()
      ]
    ])
  </div>
</div>