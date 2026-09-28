@if($item->delivered_at)
<span class="text-success">@icon('circle-check', ['mr' => 1])Delivered</span>
@elseif($item->failed_at)
<span class="text-danger">@icon('circle-x', ['mr' => 1])Failed</span>
@else
<span class="text-muted">Unknown</span>
@endif