@php($displayImage = ($product instanceof \App\Shop\eBook || $product instanceof \App\Shop\eScore) ? (app(\App\Services\Shop\DigitalMedia::class)->gallery($product)->first()['url'] ?? null) : ($product->mockup_image() ?: $product->cover_image()))
<div class="position-relative mx-auto d-block w-100" style="max-width: {{$maxWidth ?? null}}">
	@if($displayImage)<img src="{{ $displayImage }}" alt="{{ $product->title }}" class="w-100 shadow">@endif
	@if($product->isFree())
		@include('shop.components.cover.free')
	@elseif($product->hasDiscount())
		@include('shop.components.cover.discount')
	@endif

	@auth
		@if(auth()->user()->purchasesOf($product)->exists())
		@include('shop.components.cover.purchased')
		@endif
	@endauth
</div>