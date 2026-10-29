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
    @endif
</section>
