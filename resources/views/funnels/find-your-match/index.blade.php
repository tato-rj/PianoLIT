@component('layouts.funnel', [
 	'title' => 'Discover the right piece for you',
])

@slot('header')
<link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:700&display=swap" rel="stylesheet">
@endslot

<div class="text-center pt-7 mx-auto" style="width: 90%">
	<div class="mb-3">
		<h1 style="font-family: Roboto Condensed,sans-serif;" class="text-uppercase">What should I play next?</h1>
		<p class="bg-white py-2 px-3 d-inline-block rounded"><strong>Take a quick tour to find out the perfect piano piece for you!</strong></p>
	</div>

	<div class="w-100" style="background-image: url({{asset('images/misc/piano.svg')}}); height: 129px;"></div>
</div>
<section class="container py-4">
	<div class="row">
		<div class="col-lg-8 col-md-12 mx-auto">
			<div class="bg-white rounded p-3 pb-4">
				@include('funnels.find-your-match.carousel')
			</div>
			</div>
		</div>
</section>

@slot('scripts')
@include('components.addthis')
@endslot

@endcomponent