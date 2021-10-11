@php($hasMockup = $product->mockup_image())

<div class="d-block w-100 {{! $hasMockup ? 'shadow' : null}} mb-{{$mb ?? 0}} {{isset($maxWidth) ? 'mx-auto' : null}}" style="max-width: {{$maxWidth ?? null}}">
	<img src="{{$hasMockup ?? $product->cover_image()}}" class="w-100">
</div>