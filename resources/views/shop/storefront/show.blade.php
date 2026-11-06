@extends('shop.storefront.layout', ['shopTitle' => $product['name'].' | PianoLIT Shop'])

@section('shop-content')
@include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
<div data-product-detail>
    <div class="row g-4 g-lg-5 mt-1 shop-product">
        <div class="col-12 col-md-6 col-lg-7">
            @include('shop.storefront.components.product-gallery')
        </div>
        <div class="col-12 col-md-6 col-lg-5">
            <button type="button" class="btn btn-raw mb-1" data-bs-toggle="modal" data-bs-target="#product-share-modal">@icon('share-2')Share</button>
            <h2>{{ $product['name'] }}</h2>
            @if($product['subtitle'])<p class="mb-4">{{ $product['subtitle'] }}</p>@endif
            @include('shop.components.reviews.summary', ['reviewUrl' => '#reviews', 'compact' => false])
            @if($digitalProduct)
                @include('shop.storefront.components.product-price')
                @include('shop.storefront.components.digital-actions', ['product' => $digitalProduct])
            @else
                @include('shop.storefront.components.merchandise-options')
            @endif
            @include('shop.storefront.components.product-details')
            @unless($digitalProduct)
                <p class="mt-3 mb-0 shop-checkout-note">{{ app(\App\Services\Shop\CheckoutEnvironment::class)->available() ? 'Shipping and taxes are calculated at checkout.' : 'Checkout is not available yet. You can add products to your bag.' }}</p>
            @endunless
        </div>
    </div>
    @if($digitalProduct)
        @include('shop.storefront.components.digital-sections', ['product' => $digitalProduct])
    @else
        @include('shop.storefront.components.product-row', ['products' => $relatedProducts])
        <div class="mt-5">@include('shop.components.reviews.layout', ['product' => $reviewProduct, 'storefront' => true])</div>
    @endif
</div>
@endsection

@push('scripts')
    @include('components.media.link-share', [
        'id' => 'product-share-modal', 'url' => $product['url'], 'title' => $product['name'],
        'shareData' => \App\Support\LinkShare::forMessage($product['url'], $product['name'], $product['name'], 'Product from PianoLIT Shop', $product['name']),
    ])
    <script src="{{ mix('js/components/link-share.js') }}" defer></script>
    @if(!empty($product['sizeChart']))
        @include('shop.storefront.components.size-chart', ['id' => 'product-size-chart', 'sizeChart' => $product['sizeChart']])
    @endif
    @if($digitalProduct)
        <script src="{{ asset('js/vendor/turn.min.js') }}" defer></script>
    @endif
    <script src="{{ mix('js/views/shop-product.js') }}" defer></script>
@endpush
