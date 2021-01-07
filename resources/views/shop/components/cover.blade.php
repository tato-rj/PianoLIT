<div class="position-relative mx-auto d-block w-100" style="max-width: {{$maxWidth ?? null}}">
	<img src="{{$cover ?? $product->cover_image()}}" class="w-100 {{isset($cover) ? null : 'shadow'}}">
	@if($product->isFree())
		@include('shop.components.cover.free', ['top' => $top ?? null, 'left' => $left ?? null])
	@elseif($product->discount > 0 && $product->discount < 100)
		@include('shop.components.cover.discount', ['top' => $top ?? null, 'left' => $left ?? null])
	@endif

	@auth
		@if(auth()->user()->purchasesOf($product)->exists())
		@include('shop.components.cover.purchased')
		@endif
	@endauth
</div>