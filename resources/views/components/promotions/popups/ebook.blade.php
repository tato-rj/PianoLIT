@component('components.dialogs.modal', ['id' => 'modal-subscription', 'options' => ['header' => ['raw' => true]]])
@slot('header')
<h5 class="mt-2">New eBook</h5>
@if($product->hasDiscount())
<div style="margin-bottom: -8px">Get <strong class="text-red">{{$product->discount}}% OFF</strong> on this eBook</div>
@else
<div style="margin-bottom: -8px">View this eBook</div>
@endif
@endslot

@slot('body')
<div class="mb-3">
	@include('shop.components.mockup', ['maxWidth' => '200px', 'mb' => 4])
</div>
<p class="text-muted text-center">{{$product->subtitle}}</p>
<a href="{{$product->showRoute()}}" class="btn btn-primary d-block w-100">View eBook</a>
@endslot
@endcomponent