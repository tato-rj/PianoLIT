<section class="shop-details mt-4" aria-labelledby="product-details-title">
    <h2 id="product-details-title" class="mb-3">Details</h2>
    @if($digitalProduct)
        <dl class="shop-product-facts mb-0">
            <div><dt>Edition</dt><dd>{{ $productKind }} · Digital download</dd></div>
            @if($formats)<div><dt>Format</dt><dd>{{ $formats }}</dd></div>@endif
            @if($digitalProduct->pages_count)<div><dt>Length</dt><dd>{{ $digitalProduct->pages_count }} pages</dd></div>@endif
            @if($digitalProduct->author)<div><dt>Author</dt><dd>{{ $digitalProduct->author }}</dd></div>@endif
            @if($digitalProduct->published_at)<div><dt>Published</dt><dd>{{ $digitalProduct->published_at->toFormattedDateString() }}</dd></div>@endif
        </dl>
    @else
        <p>{{ $product['name'] }}</p>
        <p class="mb-0">Choose from the available options above. Browse the gallery for a closer look at the design.</p>
        @if($product['category'] === 't-shirts')
            <button type="button" class="btn btn-link px-0 mt-3" data-bs-toggle="offcanvas" data-bs-target="#product-size-chart" aria-controls="product-size-chart">@icon('ruler')Fitting and size chart</button>
        @endif
        <section class="mt-4" aria-labelledby="product-quality-returns-title">
            <h3 class="h6 mb-3" id="product-quality-returns-title">Quality Guarantee &amp; Returns</h3>
            <ul class="mb-0">
                <li class="mb-2">Quality is guaranteed. If there is a print error or visible quality issue, we'll replace or refund it.</li>
                <li>Because the products are made to order, we do not accept general returns or sizing-related returns.</li>
            </ul>
        </section>
    @endif
</section>
