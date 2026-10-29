@if($product['digital'] || $product['variants'])
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <p class="shop-price-tag mb-0" data-product-price>{{ $product['priceVaries'] && $product['price'] ? 'From ' : '' }}{{ $product['price'] ?? 'Choose an option' }}</p>
        @if($product['originalPrice'])<del class="text-muted">{{ $product['originalPrice'] }}</del>@endif
    </div>
@endif
