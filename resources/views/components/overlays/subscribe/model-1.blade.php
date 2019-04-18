@component('layouts.overlay', ['name' => 'subscribe', 'light' => true, 'position' => 'center', 'background' => '255,255,255,0.9'])
<div class="mx-3">
	<div class="rounded text-center p-6 bg-white shadow-light">
		<img src="{{asset('images/misc/subscribe-flow.svg')}}" width="200" class="mb-4">
		<div class="mb-4">
			<div>Enjoy our latest posts directly in your inbox</div>
		</div>
		
		@include('components.form.subscription')
	</div>
</div>
@endcomponent