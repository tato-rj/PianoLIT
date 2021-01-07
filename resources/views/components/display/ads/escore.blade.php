@component('components.display.ads.layout', [
	'ad' => $ad['escore'], 
	'mobile' => $mobile ?? null, 
	'vertical' => $vertical,
	'action' => 'MORE DETAILS'])

	@slot('image')
	<div class="px-3">
		@include('shop.components.mockup', ['product' => $ad['escore']])
	</div>
	@endslot

	@slot('afterTitle')
	<div class="text-left">
		<ul class="list-unstyled">
			<li class="
			@isset($mobile)
			{{$mobile ? 'lead px-4' : null}}
			@endisset
			">@fa(['icon' => 'check', 'color' => 'green'])Instant Download</li>
			<li class="
			@isset($mobile)
			{{$mobile ? 'lead px-4' : null}}
			@endisset
			">@fa(['icon' => 'check', 'color' => 'green'])Lifetime Access</li>
			<li class="
			@isset($mobile)
			{{$mobile ? 'lead px-4' : null}}
			@endisset
			">@fa(['icon' => 'check', 'color' => 'green'])One click payment</li>
		</ul>
	</div>
	@endslot
@endcomponent