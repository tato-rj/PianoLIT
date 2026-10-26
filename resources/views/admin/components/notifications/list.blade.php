@if($unreadNotifications->isNotEmpty())
<ul class="admin-notification-list" aria-label="Unread notifications">
    @foreach($unreadNotifications->groupBy('data.message') as $group)
    <li>@include('admin.components.notifications.item', ['notifications' => $group])</li>
    @endforeach
</ul>
@else
<div class="admin-notification-empty">
    <span class="admin-notification-empty-icon">@icon('bell', ['mr' => 0])</span>
    <h3>No new notifications</h3>
    <p>New updates will appear here.</p>
</div>
@endif
