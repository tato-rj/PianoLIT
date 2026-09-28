<div class="d-flex">
	<div>
		<div class="mb-2">
			@icon('copy', ['color' => 'grey'])
		</div>
		<div class="mb-2">
			@icon('cloud-download', ['color' => 'grey'])
		</div>
		<div class="mb-2">
			@icon('calendar', ['color' => 'grey'])
		</div>
		<div class="mb-2">
			@icon('pen-tool', ['color' => 'grey'])
		</div>
	</div>
	<div>
		<div class="text-muted mb-2">{{$product->pages_count}} pages</div>
		<div class="text-muted mb-2">Available in <strong>{{arrayToSentence(array_keys($product->links()))}}</strong></div>
		<div class="text-muted mb-2">Published on {{$product->published_at->toFormattedDateString()}}</div>
		<div class="text-muted mb-2">Last updated {{$product->updated_at->diffForHumans()}}</div>
	</div>
</div>