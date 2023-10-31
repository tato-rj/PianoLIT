@php($hasCard = auth()->user()->customer()->exists() && auth()->user()->customer->hasCard())

@if($hasCard)
<div class="form-group">
	<div class="custom-control custom-radio mb-2">
		<input checked type="radio" id="current-card" target="#returning-payment-form" name="payment-method" class="custom-control-input">
		<label class="custom-control-label" for="current-card">Use my <strong>{!! auth()->user()->customer->card() !!}</strong></label>
		<div class="badge cursor-pointer badge-pill alert-red ml-2" data-toggle="modal" data-target="#remove-card">remove</div>
	</div>
	<div class="custom-control custom-radio mb-2">
		<input type="radio" id="new-card" target="#payment-form" name="payment-method" class="custom-control-input">
		<label class="custom-control-label" for="new-card">Use a different payment method</label>
	</div>
</div>

<form action="{{$product->purchaseRoute()}}" method="POST" id="returning-payment-form" 
	class="mb-4 payment-forms" disable-on-submit data-key="{{(new \App\Billing\Sources\Concerns\StripeJurisdiction)->set()}}">
	@csrf
	@include('shop.components.forms.coupon')

	<button id="card-button" type="submit" class="btn btn-block btn-default">@fa(['icon' => 'lock'])Buy now for ${{$product->finalPrice()}}</button>
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