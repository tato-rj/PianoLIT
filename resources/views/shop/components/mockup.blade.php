@php($hasMockup = $product->mockup_image())

<div class="d-block w-100 {{! $hasMockup ? 'shadow' : null}}" style="max-width: {{$maxWidth ?? null}}">
	<img src="{{$hasMockup ?? $product->cover_image()}}" class="w-100" style="{{$hasMockup ? 'transform: scale(1.2);' : null}}">
</div>