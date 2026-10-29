@extends('shop.storefront.layout', ['shopTitle' => $product['name'].' | PianoLIT Shop'])

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
<div data-product-detail>
    <div class="row g-4 g-lg-5 mt-1 shop-product">
        <div class="col-12 col-md-6">
            @include('shop.storefront.components.product-gallery')
        </div>
        <div class="col-12 col-md-6">
            @include('shop.storefront.components.product-price')
            <h1 class="mb-3">{{ $product['name'] }}</h1>
            @if($product['subtitle'])<p class="mb-4">{{ $product['subtitle'] }}</p>@endif
            @if($digitalProduct)
                @include('shop.storefront.components.digital-actions', ['product' => $digitalProduct])
            @else
                @include('shop.storefront.components.merchandise-options')
            @endif
            @include('shop.storefront.components.product-details')
            @unless($digitalProduct)
                <p class="mt-3 mb-0 shop-checkout-note">{{ app(\App\Services\Shop\CheckoutEnvironment::class)->available() ? 'Shipping and taxes are calculated at checkout.' : 'Checkout is coming soon. You can browse and build your bag in the meantime.' }}</p>
            @endunless
        </div>
    </div>
    @if($digitalProduct)
        @include('shop.storefront.components.digital-sections', ['product' => $digitalProduct])
    @endif
</div>
@endsection

@if($digitalProduct)
    @push('scripts')
        <script src="{{ asset('js/vendor/turn.min.js') }}" defer></script>
        <script src="{{ mix('js/views/shop-product.js') }}" defer></script>
    @endpush
@endif
