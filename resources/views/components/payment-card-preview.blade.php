@php
    $saved = $saved ?? null;
    $add = $add ?? false;
    $brands = ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'Amex', 'discover' => 'Discover', 'diners' => 'Diners Club', 'jcb' => 'JCB', 'unionpay' => 'UnionPay'];
    $brand = $saved ? strtolower(str_replace([' ', '-'], '', $saved['brand'])) : 'unknown';
    $brand = ['americanexpress' => 'amex', 'dinersclub' => 'diners'][$brand] ?? $brand;
    $brand = array_key_exists($brand, $brands) ? $brand : 'unknown';
@endphp
@if($add)
<button type="button" class="checkout-card-preview checkout-card-preview--add" data-payment-card-add aria-controls="{{ $controlsId }}" aria-expanded="false" @if($preview ?? false) data-ds-preview @endif>
    <span class="checkout-card-art" aria-hidden="true"><span></span><span></span><span></span></span>
    <span class="checkout-card-add-label">Add new card @icon('circle-plus', ['mr' => 0])</span>
</button>
@else
@if($saved)<div class="payment-card-option">@endif
<div class="checkout-card-preview" @if($saved) data-saved-card role="img" aria-label="{{ ($brands[$brand] ?? 'Card').' ending in '.$saved['last4'].', current payment method' }}" @else data-card-preview aria-label="Payment card preview" @endif data-card-brand="{{ $brand }}" @if($preview ?? false) data-ds-preview @endif>
    <div class="checkout-card-art" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="checkout-card-top"><span class="checkout-card-chip" aria-hidden="true"></span><span data-card-brand-label>{{ $saved ? ($brands[$brand] ?? 'Card') : 'Card' }}</span></div>
    <div class="checkout-card-mask" aria-hidden="true">{{ $saved ? '•••• '.$saved['last4'] : '••••  ••••  ••••  ••••' }}</div>
    @unless($saved)<span class="visually-hidden">Card details stay securely inside Stripe’s fields.</span>@endunless
    <div class="checkout-card-bottom"><span @unless($saved) data-card-name @endunless>{{ $saved ? 'Membership payment' : 'Your name' }}</span><span class="checkout-card-network" aria-hidden="true"></span></div>
</div>
@if($saved)
    <span class="payment-card-check" aria-hidden="true">@icon('check', ['mr' => 0])</span>
</div>
@endif
@endif
