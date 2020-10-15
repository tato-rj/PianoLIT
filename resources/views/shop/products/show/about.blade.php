<div class="row mb-5 pb-5">
	<div class="col-12 py-1 mb-4 border-y">
		<div class="d-flex flex-wrap justify-content-center align-items-center text-muted" style="font-size: 80%">
			<div class="m-2">
				@fa(['icon' => 'copy', 'color' => 'grey']){{$product->pages_count}} pages
			</div>
			<div class="m-2">
				@fa(['icon' => 'cloud-download-alt', 'color' => 'grey'])Available in <strong>{{arrayToSentence(array_keys($product->links()))}}</strong>
			</div>
			<div class="m-2">
				@fa(['icon' => 'calendar-alt', 'color' => 'grey'])Published on {{$product->published_at ? $product->published_at->toFormattedDateString() : 'n/a'}}
			</div>
			<div class="m-2">
				@fa(['icon' => 'pen-nib', 'color' => 'grey'])Last updated {{$product->updated_at->diffForHumans()}}
			</div>
		</div>
	</div>
	<div class="col-12">
		<div>{!! $product->description !!}</div>
	</div>
</div>