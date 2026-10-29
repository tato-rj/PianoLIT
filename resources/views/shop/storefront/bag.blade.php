@extends('shop.storefront.layout', ['shopTitle' => 'Shopping bag | PianoLIT Shop'])

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => route('shop.home')], ['label' => 'Shopping bag']]])

@if($shoppingBag['unavailable'])
    @alert(['color' => 'yellow', 'message' => 'Some items are no longer available and have been left out of your bag.', 'dismissible' => true, 'fullX' => true])
@endif
@if(count($shoppingBag['lines']))
    <div class="row mt-1 g-4 g-lg-5">
        <div class="col-12 col-lg-8">
            @foreach($shoppingBag['lines'] as $line)
                <article class="shop-bag-item mb-3">
                    <a href="{{ $line['product']['url'] }}" class="shop-bag-item__image" tabindex="-1" aria-hidden="true">
                        @if($line['product']['image'])<img src="{{ $line['product']['image']->url }}" alt="" width="100" height="100">@else @icon('image') @endif
                    </a>
                    <div class="shop-bag-item__copy">
                        <h2><a href="{{ $line['product']['url'] }}">{{ $line['product']['name'] }}</a></h2>
                        <p class="mb-1">{{ $line['variant']['color'] }} / {{ $line['variant']['size'] }}</p>
                        <p class="shop-card__price mb-0">{{ $line['total'] }}</p>
                    </div>
                    <div class="shop-bag-item__actions">
                        <form action="{{ route('shop.bag.update', ['merchandise' => $line['product']['id'], 'variant' => $line['variant']['id']]) }}" method="POST" data-bag-quantity>
                            @csrf @method('PATCH')
                            @include('shop.storefront.components.quantity', ['quantity' => $line['quantity'], 'compact' => true, 'productName' => $line['product']['name']])
                            <button type="submit" class="shop-text-button" data-quantity-save>Update</button>
                        </form>
                        <form action="{{ route('shop.bag.remove', ['merchandise' => $line['product']['id'], 'variant' => $line['variant']['id']]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="shop-remove" type="submit" aria-label="Remove {{ $line['product']['name'] }} ({{ $line['variant']['color'] }} / {{ $line['variant']['size'] }})">@icon('trash-2')</button>
                        </form>
                    </div>
                </article>
            @endforeach
            <a class="shop-text-button d-inline-flex align-items-center gap-2 mt-3" href="{{ route('shop.home') }}">@icon('arrow-left') Continue shopping</a>
        </div>
        <aside class="col-12 col-lg-4" aria-label="Order summary">
            <div class="shop-summary">
                <h2>Order summary</h2>
                <div class="d-flex justify-content-between gap-3 py-4"><span>Subtotal</span><strong>{{ $shoppingBag['subtotal'] }}</strong></div>
                <p>Shipping and taxes will be calculated at checkout.</p>
                <div class="shop-summary__total d-flex justify-content-between gap-3 py-4"><strong>Item total</strong><strong>{{ $shoppingBag['subtotal'] }}</strong></div>
                @if(app(\App\Services\Shop\CheckoutEnvironment::class)->available())
                    <a class="shop-button shop-button--mint w-100" href="{{ route('shop.checkout') }}" data-checkout-open>Checkout</a>
                @else
                    <button class="shop-button shop-button--mint w-100" type="button" disabled aria-describedby="checkout-status">Checkout</button>
                    <p class="text-center mt-3 mb-0" id="checkout-status">Checkout is coming soon</p>
                @endif
            </div>
        </aside>
    </div>
@else
    <div class="shop-empty py-5">
        <span class="shop-empty__icon" aria-hidden="true">@icon('shopping-bag', ['mr' => 0, 'size' => '2x'])</span>
        <h2>Your bag is waiting for a little music</h2>
        <p>Find something that strikes a chord.</p>
        <a class="shop-button shop-button--mint" href="{{ route('shop.home') }}">Browse products @icon('arrow-right')</a>
    </div>
@endif
@endsection
