<div class="offcanvas offcanvas-end" id="notifications-panel" tabindex="-1" aria-labelledby="notifications-panel-title">
    <div class="offcanvas-header d-block px-4 py-3">
        <div class="d-flex d-apart">
            <h5 class="offcanvas-title" id="notifications-panel-title">Notifications</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        @if(auth()->user()->hasNewNotifications())
        <div class="">
            <a href="{{route('admin.notifications.read', ['url' => url()->current()])}}"><small>Mark all as read</small></a>
        </div>
        @endif
    </div>

    <div class="offcanvas-body px-4 py-3">
        <div class="list-group">
            @forelse(auth()->user()->unreadNotifications->groupBy('data.message') as $group)
                @include('components.panel.item', ['notifications' => $group])
            @empty
            <i class="text-muted">You have no new notifications</i>
            @endforelse
        </div>
    </div>
    <div class="bg-light px-4 py-3 flex-shrink-0">
        <div class="text-center">
            <a href="{{route('admin.notifications.index')}}"><small>See all</small></a>
        </div>
    </div>
</div>
