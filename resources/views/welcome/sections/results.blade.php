@component('layouts.overlay', ['name' => 'results'])
<div class="d-flex flex-center w-100 h-100">
	<div class="text-center">
		<img src="{{asset('images/icons/smiling.svg')}}" width="80" class="mb-4">
		<h4 class="mb-4">We found <span class="text-primary">27</span> pieces!</h4>
		<a href="#" class="btn btn-primary btn-wide shadow"><i class="fab fa-apple mr-3"></i>Download the app now to see them!</a>
	</div>
</div>
@endcomponent