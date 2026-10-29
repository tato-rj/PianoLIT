@if($product->description || $product->topics->count())
    <section class="shop-product-section mt-5 pt-4" aria-labelledby="product-about-title">
        <h2 class="mb-3" id="product-about-title">About this {{ $productKind }}</h2>
        <div class="shop-product-description">{!! $product->description !!}</div>
        @if($product->topics->count())
            <div class="d-flex flex-wrap gap-2 mt-4" aria-label="Topics">
                @foreach($product->topics as $topic)
                    <a class="shop-button" href="{{ $product->indexRoute().'?'.http_build_query(['topics' => [$topic->slug]]) }}">{{ $topic->name }}</a>
                @endforeach
            </div>
        @endif
    </section>
@endif
<div class="mt-5">@include('shop.components.reviews.layout', ['storefront' => true])</div>
@if($relatedProducts->count())
    <section class="shop-product-section pt-4 mt-5" aria-labelledby="product-related-title">
        <h2 id="product-related-title" class="mb-4">More to explore</h2>
        <div class="row g-3 g-md-4">
            @foreach($relatedProducts as $related)
                <div class="col-6 col-lg-3">@include('shop.storefront.components.card', ['product' => $related, 'cardHeading' => 'h3'])</div>
            @endforeach
        </div>
    </section>
@endif
@include('shop.components.preview.magazine', ['storefront' => true])
