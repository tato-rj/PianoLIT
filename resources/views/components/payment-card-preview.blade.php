@php
    $saved = $saved ?? null;
    $add = $add ?? false;
    $brands = ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'Amex', 'discover' => 'Discover', 'diners' => 'Diners Club', 'jcb' => 'JCB', 'unionpay' => 'UnionPay'];
    $brand = $saved ? strtolower(str_replace([' ', '-'], '', $saved['brand'])) : 'unknown';
    $brand = ['americanexpress' => 'amex', 'dinersclub' => 'diners'][$brand] ?? $brand;
    $brand = array_key_exists($brand, $brands) ? $brand : 'unknown';
@endphp
@if($add)
<button type="button" class="checkout-card-preview checkout-card-preview--add" data-payment-card-add aria-controls="{{ $modalId ?? $controlsId }}" @isset($modalId) data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" aria-haspopup="dialog" @else aria-expanded="false" @endisset @if($preview ?? false) data-ds-preview @endif>
    <span class="checkout-card-art" aria-hidden="true"><span></span><span></span><span></span></span>
    <span class="checkout-card-add-label">Add new card @icon('circle-plus', ['mr' => 0])</span>
</button>
@else
@if($saved)
    @if($selectable ?? false)
    <button type="button" class="payment-card-option payment-card-option--selectable" data-checkout-saved-card aria-pressed="true" aria-label="{{ ($brands[$brand] ?? 'Card').' ending in '.$saved['last4'] }}">
    @else
    <div class="payment-card-option">
    @endif
@endif
<div class="checkout-card-preview" @if($saved) data-saved-card role="img" aria-label="{{ ($brands[$brand] ?? 'Card').' ending in '.$saved['last4'].(($selectable ?? false) ? ', saved card' : ', current payment method') }}" @else data-card-preview aria-label="Payment card preview" @endif data-card-brand="{{ $brand }}" @if($preview ?? false) data-ds-preview @endif>
    <div class="checkout-card-art" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="checkout-card-top"><span class="checkout-card-chip" aria-hidden="true"></span><span data-card-brand-label>{{ $saved ? ($brands[$brand] ?? 'Card') : 'Card' }}</span></div>
    <div class="checkout-card-mask" aria-hidden="true">{{ $saved ? '•••• '.$saved['last4'] : '••••  ••••  ••••  ••••' }}</div>
    @unless($saved)<span class="visually-hidden">Card details stay securely inside Stripe’s fields.</span>@endunless
    <div class="checkout-card-bottom"><span @unless($saved) data-card-name @endunless>{{ $saved ? 'Membership payment' : 'Your name' }}</span><span class="checkout-card-network" aria-hidden="true"></span></div>
</div>
@if($saved)
    <span class="payment-card-check" @if($selectable ?? false) data-checkout-card-check @endif aria-hidden="true">@icon('check', ['mr' => 0])</span>
    @if($selectable ?? false)</button>@else</div>@endif
@endif
@endif
