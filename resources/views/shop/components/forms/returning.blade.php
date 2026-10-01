@php($hasCard = auth()->user()->customer()->exists() && auth()->user()->customer->hasCard())

@if($hasCard)
<div class="form-group">
	<div class="form-check mb-2">
		<input checked type="radio" id="current-card" target="#returning-payment-form" name="payment-method" class="form-check-input">
		<label class="form-check-label" for="current-card">Use my <strong>{!! auth()->user()->customer->card() !!}</strong></label>
		<div class="badge cursor-pointer rounded-pill alert-red ms-2" data-bs-toggle="modal" data-bs-target="#remove-card">remove</div>
	</div>
	<div class="form-check mb-2">
		<input type="radio" id="new-card" target="#payment-form" name="payment-method" class="form-check-input">
		<label class="form-check-label" for="new-card">Use a different payment method</label>
	</div>
</div>

<form action="{{$product->purchaseRoute()}}" method="POST" id="returning-payment-form"
	class="mb-4 payment-forms" disable-on-submit data-key="{{(new \App\Billing\Sources\Concerns\StripeJurisdiction)->set($getKey = true)}}">
	@csrf
	@include('shop.components.forms.coupon')

	<button id="card-button" type="submit" class="btn d-block w-100 btn-default">@icon('lock')Buy now for ${{$product->finalPrice()}}</button>
</form>

@include('shop.components.forms.remove-card')
@endif

@include('shop.components.forms.new', [
	'hide' => $hasCard,
	'action' => $product->purchaseRoute(),
	'saveCard' => true,
	'label' => 'Buy now for $' . $product->finalPrice(),
	'comments' => 'After your payment is complete, you will receive an email with the link to download the eBook. You can also access it from your purchases page, located under the main menu.'
	])