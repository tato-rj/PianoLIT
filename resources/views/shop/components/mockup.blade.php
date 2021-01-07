<div class="d-block w-100" style="max-width: {{$maxWidth ?? null}}">
	<img src="{{$product->mockup_image() ?? $product->cover_image()}}" class="w-100" style="{{$product->mockup_image() ? 'transform: scale(1.2);' : null}}">
</div>