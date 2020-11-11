@component('components.display.ads.layout', [
	'ad' => $ad['ebook'], 
	'mobile' => $mobile ?? null, 
	'vertical' => $vertical,
	'action' => 'DOWNLOAD NOW'])

	@slot('image')
	<div class="px-3"> 
		@include('shop.components.cover', ['product' => $ad['ebook'], 'maxWidth' => '300px'])
	</div>
	@endslot

	@slot('beforeTitle')
	<p>{{$ad['ebook']->subtitle}}</p>
	@endslot
@endcomponent
