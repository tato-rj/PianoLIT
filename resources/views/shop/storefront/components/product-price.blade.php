@if($product['digital'] || $product['variants'])
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-4 fs-4">
        @if($product['originalPrice'])<del class="text-muted">{{ $product['originalPrice'] }}</del>@endif
        <p class="shop-price-tag fw-bold mb-0" data-product-price>{{ $product['priceVaries'] && $product['price'] ? 'From ' : '' }}{{ $product['price'] ?? 'Choose an option' }}</p>
    </div>
@endif
