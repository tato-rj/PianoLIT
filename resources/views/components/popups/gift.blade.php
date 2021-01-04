@isset($gift)
@component('components.modal', ['id' => 'modal-gift', 'cookie' => 'subscription-popup'])
@slot('body')
<div class="text-center">
	<img src="{{storage($gift->thumbnail_path)}}" alt="{{$gift->name}}" class="border mb-3" style="width: 210px">
	<div class="">
		<div class="mb-4">
			<div>Subscribe today and get a <strong><u>FREE</u></strong> poster in your inbox!</div>
		</div>
		@include('components.form.subscription', ['formId' => 'gift-subscription-form', 'label' => 'SUBSCRIBE NOW','gift_url' => route('infographs.download', $gift->slug)])
	</div>
</div>
@endslot
@endcomponent
@endisset