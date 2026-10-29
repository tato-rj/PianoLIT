@php
  $notificationQuery = auth('admin')->user()->unreadNotifications();
  $unreadNotificationCount = (clone $notificationQuery)->count();
  $unreadNotifications = $unreadNotificationCount
    ? $notificationQuery->limit(\App\Support\AdminPagination::PER_PAGE)->get(['id', 'data', 'created_at'])
    : collect();
@endphp
<a class="admin-notifications notifications-link" href="#notifications-panel" role="button" aria-label="Notifications ({{$unreadNotificationCount}} unread)" data-bs-toggle="offcanvas" data-bs-target="#notifications-panel" aria-controls="notifications-panel">
  @icon('bell', ['mr' => 0, 'solid' => $unreadNotificationCount > 0, 'classes' => 'notification-bell'])
  <span class="admin-notification-dot" aria-hidden="true" @if(!$unreadNotificationCount) hidden @endif>{{$unreadNotificationCount}}</span>
</a>

@push('admin-notifications')
  @include('admin.components.notifications.panel')
@endpush
