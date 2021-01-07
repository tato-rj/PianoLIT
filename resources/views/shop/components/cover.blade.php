<div class="position-relative mx-auto d-block w-100" style="max-width: {{$maxWidth ?? null}}">
	<img src="{{$cover ?? $product->cover_image()}}" class="w-100 shadow">
	@if($product->isFree())
		@include('shop.components.cover.free')
	@elseif($product->discount > 0 && $product->discount < 100)
		@include('shop.components.cover.discount')
	@endif

	@auth
		@if(auth()->user()->purchasesOf($product)->exists())
		@include('shop.components.cover.purchased')
		@endif
	@endauth
</div>