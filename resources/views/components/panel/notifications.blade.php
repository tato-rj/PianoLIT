<div class="offcanvas offcanvas-end" id="notifications-panel" tabindex="-1" aria-labelledby="notifications-panel-title" data-panel-url="{{route('admin.notifications.panel')}}" data-preview-limit="{{\App\Support\AdminPagination::PER_PAGE}}">
    <div class="offcanvas-header admin-notification-header">
        <div class="admin-notification-heading">
            <div>
                <h2 class="offcanvas-title" id="notifications-panel-title">Notifications<span class="admin-heading-dot" aria-hidden="true">.</span></h2>
                <p class="admin-notification-summary" data-notification-summary aria-live="polite"></p>
            </div>
            <button type="button" class="admin-notification-close" data-bs-dismiss="offcanvas" aria-label="Close notifications">@icon('x', ['mr' => 0])</button>
        </div>
        <form action="{{route('admin.notifications.read')}}" method="POST" data-notification-read-all hidden>
            @csrf
            <button type="submit" class="admin-notification-read">@icon('check', ['mr' => 0]) Mark all as read</button>
        </form>
        <p class="small text-danger mt-3 mb-0" data-notification-error role="alert" hidden></p>
        <button type="button" class="btn btn-secondary btn-sm mt-2" data-notification-retry hidden>Try again</button>
    </div>

    <div class="offcanvas-body admin-notification-body" data-notification-body>
        <p class="text-muted" role="status">Loading notifications…</p>
    </div>
    <div class="admin-notification-footer">
        <a href="{{route('admin.notifications.index')}}">View all notifications @icon('arrow-right', ['mr' => 0])</a>
    </div>
</div>
