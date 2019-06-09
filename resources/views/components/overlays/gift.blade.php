@component('layouts.overlay', ['name' => 'gift-post', 'light' => false, 'position' => 'center', 'background' => '0,0,0,0.8'])
<div class="mx-3" style="max-width: 412px">
	<div class="rounded text-center shadow-light bg-white">
		<img src="{{$image}}" class="border mt-4" style="width: 180px">
		<div class="px-4 py-3">
			<div class="mb-3">
				<div>We'll send you this gift directly on your inbox!</div>
			</div>
			
			@include('components.form.subscription', ['gift' => $image, 'label' => 'SEND ME THE GIFT'])
		</div>
	</div>
</div>
@endcomponent