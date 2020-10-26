@if(! $collection->isEmpty())
<div class="mb-5">
	<h5 class="mb-4">{!! $title !!}</h5>
	<div class="grid row t-2" style="opacity: 0; transform: translateY(20px);">
		@foreach($collection as $item)
		@include($card)
		@endforeach
	</div>
</div>
@endif