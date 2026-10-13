@php($unreadNotifications = auth('admin')->user()->unreadNotifications)
<div class="offcanvas offcanvas-end" id="notifications-panel" tabindex="-1" aria-labelledby="notifications-panel-title">
    <div class="offcanvas-header admin-notification-header">
        <div class="admin-notification-heading">
            <div>
                <h2 class="offcanvas-title" id="notifications-panel-title">Notifications<span class="admin-heading-dot" aria-hidden="true">.</span></h2>
                <p class="admin-notification-summary">
                    @if($unreadNotifications->isNotEmpty())
                    {{$unreadNotifications->count()}} unread {{$unreadNotifications->count() === 1 ? 'notification' : 'notifications'}}
                    @else
                    You're all caught up
                    @endif
                </p>
            </div>
            <button type="button" class="admin-notification-close" data-bs-dismiss="offcanvas" aria-label="Close notifications">@icon('x', ['mr' => 0])</button>
        </div>
        @if($unreadNotifications->isNotEmpty())
        <a class="admin-notification-read" href="{{route('admin.notifications.read', ['url' => url()->current()])}}">
            @icon('check', ['mr' => 0]) Mark all as read
        </a>
        @endif
    </div>

    <div class="offcanvas-body admin-notification-body">
        @if($unreadNotifications->isNotEmpty())
        <ul class="admin-notification-list" aria-label="Unread notifications">
            @foreach($unreadNotifications->groupBy('data.message') as $group)
            <li>@include('components.panel.item', ['notifications' => $group])</li>
            @endforeach
        </ul>
        @else
        <div class="admin-notification-empty">
            <span class="admin-notification-empty-icon">@icon('bell', ['mr' => 0])</span>
            <h3>No new notifications</h3>
            <p>New updates will appear here.</p>
        </div>
        @endif
    </div>
    <div class="admin-notification-footer">
        <a href="{{route('admin.notifications.index')}}">View all notifications @icon('arrow-right', ['mr' => 0])</a>
    </div>
</div>
