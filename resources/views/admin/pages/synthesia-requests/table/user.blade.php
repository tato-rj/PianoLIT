@if($synthesiaRequest->user)
    <a href="{{ route('admin.users.show', $synthesiaRequest->user_id) }}" class="admin-table__primary">{{ $synthesiaRequest->user->full_name }}</a>
    @php($source = optional($synthesiaRequest->user->membership)->source)
    @if($source && $source->created_at && $source->renews_at && $synthesiaRequest->user->isOnTrial)
        @if($synthesiaRequest->user->membership->created_at)
            @php($daysLeft = max(0, 7 - $synthesiaRequest->user->membership->created_at->diffInDays(now())))
            <div><span class="admin-table__badge admin-table__badge--warning">{{ $daysLeft }} {{ str_plural('day', $daysLeft) }} left</span></div>
        @else
            <div><span class="admin-table__badge admin-table__badge--warning">On trial</span></div>
        @endif
    @endif
@else
    <span class="text-muted">Account unavailable</span>
@endif
