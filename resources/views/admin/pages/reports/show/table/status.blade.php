<div class="d-flex">
	@if($item->delivered_at)
	<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Delivered</span>
	@elseif($item->failed_at)
	<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Failed</span>
	@else
	<span class="text-muted">Unknown</span>
	@endif
	<span class="ml-2">#{{$item->id}}</span>
</div>