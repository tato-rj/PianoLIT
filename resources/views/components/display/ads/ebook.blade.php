@component('components.display.ads.layout', [
	'ad' => $ad['ebook'], 
	'mobile' => $mobile ?? null, 
	'vertical' => $vertical,
	'action' => 'DOWNLOAD NOW'])

	@slot('header')
	<div class="mb-2">
		<h5 class="m-0 text-primary">Piano<strong>LIT</strong></h5>
		<p class="font-cursive mb-0" style="font-size: 4em; margin-top: -30px;">eBooks</p>
	</div>
	@endslot

	@slot('image')
	<div class="px-3"> 
		@include('shop.components.cover', ['product' => $ad['ebook'], 'maxWidth' => '300px'])
	</div>
	@endslot

	@slot('beforeTitle')
	<p>{{$ad['ebook']->subtitle}}</p>
	@endslot
@endcomponent
