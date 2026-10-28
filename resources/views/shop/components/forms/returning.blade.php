@php
    $customer = auth()->user()->customer;
    $hasCard = $customer && $customer->hasCard();
    $useNewCard = ! $hasCard || old('payment_method') === 'new';
@endphp

@if($hasCard)
<fieldset class="mb-4">
    <legend class="visually-hidden">Choose a payment method</legend>
    <div class="form-check mb-3">
        <input type="radio" id="current-card" data-payment-target="returning-payment-form" name="payment-method" class="form-check-input" @unless($useNewCard) checked @endunless aria-controls="returning-payment-form">
        <label class="form-check-label" for="current-card">{{ ucfirst($customer->card_brand) }} ending in {{ $customer->card_last_four }}</label>
    </div>
    <div class="form-check">
        <input type="radio" id="new-card" data-payment-target="payment-form" name="payment-method" class="form-check-input" @if($useNewCard) checked @endif aria-controls="payment-form">
        <label class="form-check-label" for="new-card">Use a new card</label>
    </div>
</fieldset>
<form action="{{ $product->purchaseRoute() }}" method="POST" id="returning-payment-form" data-shop-payment="saved" @if($useNewCard) hidden @endif>
    @csrf
    <input type="hidden" name="payment_method" value="saved">
    <input type="hidden" name="coupon" value="" data-coupon-value>
    <button type="submit" class="btn btn-primary w-100" data-order-pay>Pay ${{ number_format($product->finalPrice(), 2) }}</button>
<div class="mt-3"><button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#remove-card">Remove saved card</button></div>
</form>
@include('shop.components.forms.remove-card')
@endif
@include('shop.components.forms.new', ['hide' => ! $useNewCard])
