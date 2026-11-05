@if($products->count())
    @php($rowId = $rowId ?? 'product-related')
    @once
        <script>{!! file_get_contents(resource_path('js/components/product-row.js')) !!}</script>
    @endonce
    <section class="mt-5 pt-4 border-top" aria-labelledby="{{ $rowId }}-title" data-product-row>
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <h2 id="{{ $rowId }}-title" class="mb-0">You may also like</h2>
            <div class="d-flex gap-2" data-product-row-controls hidden>
                <button type="button" class="btn btn-secondary btn-sm" data-product-row-scroll="-1" aria-label="Previous products" aria-controls="{{ $rowId }}-track">@icon('chevron-left', ['mr' => 0])</button>
                <button type="button" class="btn btn-secondary btn-sm" data-product-row-scroll="1" aria-label="Next products" aria-controls="{{ $rowId }}-track">@icon('chevron-right', ['mr' => 0])</button>
            </div>
        </div>
        <div class="shop-product-row" id="{{ $rowId }}-track" role="list" aria-label="Other products in this category" tabindex="0" data-product-row-track>
            @foreach($products as $related)
                <div role="listitem">@include('shop.storefront.components.card', ['product' => $related, 'cardHeading' => 'h3'])</div>
            @endforeach
        </div>
    </section>
    <script>window.PianoLITProductRow(document.currentScript.previousElementSibling, window);</script>
@endif
