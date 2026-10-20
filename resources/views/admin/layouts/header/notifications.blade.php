@php($unreadNotificationCount = auth('admin')->user()->unreadNotifications->count())
<a class="admin-notifications notifications-link" href="#notifications-panel" role="button" aria-label="Notifications ({{$unreadNotificationCount}} unread)" data-bs-toggle="offcanvas" data-bs-target="#notifications-panel" aria-controls="notifications-panel">
  @icon('bell', ['mr' => 0, 'solid' => true, 'classes' => 'notification-bell'])
  @if($unreadNotificationCount > 0)<span class="admin-notification-dot" aria-hidden="true">{{$unreadNotificationCount}}</span>@endif
</a>
