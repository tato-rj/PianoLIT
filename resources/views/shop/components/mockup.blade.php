@php($displayImage = ($product instanceof \App\Shop\eBook || $product instanceof \App\Shop\eScore) ? (app(\App\Services\Shop\DigitalMedia::class)->gallery($product)->first()['url'] ?? null) : ($product->mockup_image() ?: $product->cover_image()))
@php($hasMockup = $product->mockup_image())

<div class="d-block w-100 {{! $hasMockup ? 'shadow' : null}} mb-{{$mb ?? 0}} {{isset($maxWidth) ? 'mx-auto' : null}}" style="max-width: {{$maxWidth ?? null}}">
	@if($displayImage)<img src="{{ $displayImage }}" alt="{{ $product->title }}" class="w-100">@endif
</div>