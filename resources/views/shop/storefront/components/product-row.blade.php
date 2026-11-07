@if($products->count())
    @php($rowId = $rowId ?? 'product-related')
    <section class="mt-5 pt-4 border-top" aria-labelledby="{{ $rowId }}-title" data-product-row>
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <h4 id="{{ $rowId }}-title" class="mb-0">You may also like</h4>
            <a href="{{ app(\App\Services\Shop\Storefront::class)->categoryUrl($products->first()['category'] ?? null) }}" class="text-nowrap">View all</a>
        </div>
        <div class="shop-product-row" id="{{ $rowId }}-track" role="list" aria-label="Other products in this category" tabindex="0" data-product-row-track>
            @foreach($products as $related)
                <div role="listitem">@include('shop.storefront.components.card', ['product' => $related, 'cardHeading' => 'h3'])</div>
            @endforeach
        </div>
    </section>
@endif
