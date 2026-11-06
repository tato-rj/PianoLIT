@extends('shop.storefront.layout', ['shopTitle' => 'Shopping bag | PianoLIT Shop', 'shopBodyClass' => 'shop-page shop-bag-page'])

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => route('shop.home')], ['label' => 'Shopping bag']]])

@if($shoppingBag['unavailable'])
    @alert(['color' => 'yellow', 'message' => 'Some items are no longer available and have been left out of your bag.', 'dismissible' => true, 'fullX' => true])
@endif
@if(count($shoppingBag['lines']))
    <div class="row mt-1 g-4 g-lg-5">
        <div class="col-12 col-lg-8" data-bag-items>
            @foreach($shoppingBag['lines'] as $line)
                <article class="shop-bag-item border rounded-sm mb-3">
                    <a href="{{ $line['product']['url'] }}" class="shop-bag-item__image border rounded-sm overflow-hidden" tabindex="-1" aria-hidden="true">
                        @if($line['product']['image'])<img src="{{ $line['product']['image']->url }}" alt="" width="100" height="100">@else @icon('image') @endif
                    </a>
                    <div class="shop-bag-item__copy">
                        <h2><a href="{{ $line['product']['url'] }}">{{ $line['product']['name'] }}</a></h2>
                        <p class="mb-1">{{ $line['variant']['color'] }} / {{ $line['variant']['size'] }}</p>
                        <p class="shop-card__price mb-0">{{ $line['total'] }}</p>
                    </div>
                    <div class="shop-bag-item__actions d-flex flex-row-reverse flex-md-column align-items-center align-items-md-end align-self-stretch justify-content-between gap-3 flex-shrink-0">
                        <form action="{{ route('shop.bag.update', ['merchandise' => $line['product']['id'], 'variant' => $line['variant']['id']]) }}" method="POST" class="d-flex align-items-center gap-2" data-bag-quantity>
                            @csrf @method('PATCH')
                            @include('shop.storefront.components.quantity', ['quantity' => $line['quantity'], 'compact' => true, 'productName' => $line['product']['name']])
                            <button type="submit" class="btn btn-secondary btn-sm" data-quantity-save>Update</button>
                        </form>
                        <form action="{{ route('shop.bag.remove', ['merchandise' => $line['product']['id'], 'variant' => $line['variant']['id']]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-red btn-sm" type="submit" aria-label="Remove {{ $line['product']['name'] }} ({{ $line['variant']['color'] }} / {{ $line['variant']['size'] }})">@icon('trash-2', ['mr' => 0])</button>
                        </form>
                    </div>
                </article>
            @endforeach
            <a class="d-inline-flex align-items-center gap-2 mt-3" href="{{ route('shop.home') }}">@icon('arrow-left') Continue shopping</a>
        </div>
        <aside class="col-12 col-lg-4" aria-label="Order summary">
            <div class="shop-summary border rounded-sm">
                <h2 class="border-bottom">Order summary</h2>
                <div class="d-flex justify-content-between gap-3 py-4"><span>Subtotal</span><strong>{{ $shoppingBag['subtotal'] }}</strong></div>
                <p>Shipping and taxes will be calculated at checkout.</p>
                <div class="border-top d-flex justify-content-between gap-3 py-4"><strong>Item total</strong><strong>{{ $shoppingBag['subtotal'] }}</strong></div>
                @if($expressCheckout)
                    <div data-checkout-surface class="shop-bag-express">
                        @include('shop.storefront.checkout.payment', $expressCheckout + ['walletOnly' => true])
                        <div hidden aria-hidden="true">
                            <span data-checkout-total></span><span data-checkout-subtotal></span><span data-checkout-shipping></span>
                            <span data-checkout-tax></span><span data-checkout-discount-line></span><span data-checkout-discount></span>
                        </div>
                    </div>
                @endif
                @if(app(\App\Services\Shop\CheckoutEnvironment::class)->available())
                    <div class="shop-bag-checkout"><a class="btn btn-primary w-100" href="{{ route('shop.checkout') }}">Checkout with card @icon('arrow-right', ['ml' => 2, 'mr' => 0])</a></div>
                @else
                    <button class="btn btn-primary w-100" type="button" disabled aria-describedby="checkout-status">Checkout with card</button>
                    <p class="text-center mt-3 mb-0" id="checkout-status">Checkout is not available yet</p>
                @endif
            </div>
        </aside>
    </div>
@else
    <div class="shop-empty py-5">
        <span class="shop-empty__icon bg-light text-muted rounded-circle" aria-hidden="true">@icon('shopping-bag', ['mr' => 0, 'size' => '2x'])</span>
        <h2>Your bag is empty</h2>
        <p>Products you add will appear here.</p>
        <a class="btn btn-primary" href="{{ route('shop.home') }}">Browse products @icon('arrow-right')</a>
    </div>
@endif
@endsection

@if($expressCheckout)
    @push('scripts')
        <script src="https://js.stripe.com/v3/" defer></script>
        <script src="{{ mix('js/components/express-checkout.js') }}" defer></script>
        <script src="{{ mix('js/views/merchandise-checkout.js') }}" defer></script>
    @endpush
@endif
