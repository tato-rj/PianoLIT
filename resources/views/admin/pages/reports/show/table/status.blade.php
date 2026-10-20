@if($item->delivered_at)
    <span class="admin-table__badge admin-table__badge--green">Delivered</span>
@elseif($item->failed_at)
    <span class="admin-table__badge admin-table__badge--red">Failed</span>
@else
    <span class="admin-table__badge admin-table__badge--muted">Unknown</span>
@endif
