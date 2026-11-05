@if($product->description || $product->topics->count())
    <section class="border-top mt-5 pt-4" aria-labelledby="product-about-title">
        <h4 class="mb-3" id="product-about-title">About this {{ $productKind }}</h4>
        <div class="shop-product-description">{!! $product->description !!}</div>
        @if($product->topics->count())
            <div class="d-flex flex-wrap gap-2 mt-4" aria-label="Topics">
                @foreach($product->topics as $topic)
                    <a class="btn btn-secondary" href="{{ $product->indexRoute().'?'.http_build_query(['topics' => [$topic->slug]]) }}">{{ $topic->name }}</a>
                @endforeach
            </div>
        @endif
    </section>
@endif
@include('shop.storefront.components.product-row', ['products' => $relatedProducts])
<div class="mt-5">@include('shop.components.reviews.layout', ['storefront' => true])</div>
@include('shop.components.preview.magazine', ['storefront' => true])
