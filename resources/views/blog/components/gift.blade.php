<button class="position-fixed bg-white shadow rounded-pill px-3 py-2 d-flex align-items-center border-0" id="gift" style="bottom: 40px; right: 30px">
	<span class="mr-2 text-muted"><small>We have a gift for you!</small></span>
	<i class="fa-1x fas fa-gift animated infinite bounce delay-4s" style="color: #E92C59"></i>
</button>

@component('components.modal', ['id' => 'modal-gift'])
@slot('body')
<div class="text-center">
	<img src="{{$post->gift_path}}" class="border mb-3" style="width: 210px">
	<div class="">
		<div class="mb-3">
			<div>We'll send you this gift directly on your inbox!</div>
		</div>
		
		@include('components.form.subscription', ['gift' => $post->gift_path, 'label' => 'SEND ME THE GIFT'])
	</div>
</div>
@endslot
@endcomponent