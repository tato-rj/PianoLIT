@component('components.display.ads.layout', [
	'ad' => $ad['crashcourse'], 
	'mobile' => $mobile ?? null,
	'vertical' => $vertical,
	'action' => 'START NOW'])

	@slot('header')
	<div class="mb-3">
		<h5 class="m-0 ">CRASH<strong>COURSE</strong></h5>
		<p class="text-primary mb-0" style="line-height: 1.2"><i><strong>Daily lessons delivered to your email</strong></i></p>
	</div>
	@endslot

	@slot('image')
	<img src="{{$ad['crashcourse']->cover_image()}}" class="w-100">
	@endslot

	@slot('beforeTitle')
	<p>{{$ad['crashcourse']->description}}</p>
	@endslot
@endcomponent