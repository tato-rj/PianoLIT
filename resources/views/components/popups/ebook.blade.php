@component('components.modal', ['id' => 'modal-subscription', 'options' => ['header' => ['raw' => true]]])
@slot('header')
<h5 class="mt-2">Don't miss out!</h5>
<div style="margin-bottom: -8px">Get 50% off on our new eBook now👇</div>
@endslot

@slot('body')
@php($ebook = \App\Shop\eBook::latest()->first())
<div class="mb-3">
	@include('shop.components.cover', ['product' => $ebook])
</div>
<a href="{{$ebook->showRoute()}}" class="btn btn-primary btn-block">Download this eBook now!</a>
@endslot
@endcomponent