<div class="offcanvas offcanvas-end" id="notifications-panel" tabindex="-1" aria-labelledby="notifications-panel-title" data-preview-limit="{{\App\Support\AdminPagination::PER_PAGE}}">
    <div class="offcanvas-header admin-notification-header">
        <div class="admin-notification-heading">
            <div>
                <h2 class="offcanvas-title" id="notifications-panel-title">Notifications<span class="admin-heading-dot" aria-hidden="true">.</span></h2>
                <p class="admin-notification-summary" data-notification-summary aria-live="polite">
                    @if($unreadNotificationCount)
                        {{$unreadNotificationCount}} unread {{$unreadNotificationCount === 1 ? 'notification' : 'notifications'}}@if($unreadNotificationCount > \App\Support\AdminPagination::PER_PAGE) · Showing latest {{\App\Support\AdminPagination::PER_PAGE}}@endif
                    @else
                        You're all caught up
                    @endif
                </p>
            </div>
            <button type="button" class="admin-notification-close" data-bs-dismiss="offcanvas" aria-label="Close notifications">@icon('x', ['mr' => 0])</button>
        </div>
        <form action="{{route('admin.notifications.read')}}" method="POST" data-notification-read-all @if(!$unreadNotificationCount) hidden @endif>
            @csrf
            <button type="submit" class="admin-notification-read">@icon('check', ['mr' => 0]) Mark all as read</button>
        </form>
        <p class="small text-danger mt-3 mb-0" data-notification-error role="alert" hidden></p>
    </div>

    <div class="offcanvas-body admin-notification-body" data-notification-body>
        @include('admin.components.notifications.list')
    </div>
    <div class="admin-notification-footer">
        <a href="{{route('admin.notifications.index')}}">View all notifications @icon('arrow-right', ['mr' => 0])</a>
    </div>
</div>
