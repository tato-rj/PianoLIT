@component('layouts.overlay', ['name' => 'results'])
<div class="d-flex flex-center w-100 h-100">
	<div class="text-center searching">
		@include('components.animations.searching')
	</div>
	<div class="text-center positive-results" style="display: none;">
		<img src="{{asset('images/icons/smiling.svg')}}" width="80" class="mb-4">
		<p class="mb-4" style="font-size: 1.4em">We found <span class="text-primary results-count"></span> pieces!</p>
		<a href="#" class="btn btn-primary btn-wide shadow"><i class="fab fa-apple mr-3"></i>Download the app now to see them!</a>
	</div>
	<div class="text-center empty-results" style="display: none;">
		<img src="{{asset('images/icons/sad.svg')}}" width="80" class="mb-4">
		<p class="text-muted">We couldn't find any pieces matching your selection...</p>
		<p><strong>Please try again!</strong></p>
	</div>
</div>
@endcomponent