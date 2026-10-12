<a class="admin-notifications notifications-link" href="#notifications-panel" role="button" aria-label="Notifications ({{auth('admin')->user()->unreadNotifications->count()}} unread)" data-bs-toggle="offcanvas" data-bs-target="#notifications-panel" aria-controls="notifications-panel">
  @icon('bell', ['mr' => 0, 'classes' => 'notification-bell'])
  @if(auth('admin')->user()->hasNewNotifications())<span class="admin-notification-dot"></span>@endif
</a>
