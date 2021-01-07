@component('components.display.ads.layout', [
	'ad' => $ad['ebook'], 
	'mobile' => $mobile ?? null, 
	'vertical' => $vertical,
	'action' => 'DOWNLOAD NOW'])

	@slot('image')
	<div class="px-3"> 
		@include('shop.components.mockup', ['product' => $ad['ebook']])
	</div>
	@endslot

	@slot('beforeTitle')
	<p>{{$ad['ebook']->subtitle}}</p>
	@endslot
@endcomponent
