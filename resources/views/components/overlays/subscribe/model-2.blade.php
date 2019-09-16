@component('layouts.overlay', ['name' => 'gift', 'light' => false, 'position' => 'center', 'background' => '0,0,0,0.8'])
<div class="mx-3" style="max-width: 512px">
	<div class="rounded text-center shadow-light bg-white pt-4">
		@php($gift = randval(['modes', 'chords', 'intervals', 'rhythm-values']).'.jpg')
		<img src="{{asset('images/gifts/'.$gift)}}" class="rounded-top border" style="width: 210px">
		<div class="px-4 py-3 bg-white rounded-bottom">
			<div class="mb-4">
				<div>Subscribe today and get a <strong><u>FREE</u></strong> poster in your inbox!</div>
			</div>
			
			@include('components.form.subscription', ['gift' => 'images/gifts/'.$gift])
		</div>
	</div>
</div>
@endcomponent