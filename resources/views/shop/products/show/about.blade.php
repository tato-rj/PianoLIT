<section class="row g-4 g-lg-5 mb-5" aria-labelledby="product-about-title">
    <div class="col-md-8">
        <h2 class="h5 mb-3" id="product-about-title">About this {{ $productKind }}</h2>
        <div class="product-detail__description">{!! $product->description !!}</div>
    </div>
    <div class="col-md-4">
        <h2 class="h5 mb-3">At a glance</h2>
        <dl class="product-detail__facts mb-0">
            <div><dt>@icon('copy')Length</dt><dd>{{ $product->pages_count }} pages</dd></div>
            <div><dt>@icon('file-down')Format</dt><dd>{{ implode(' + ', array_keys($product->links())) }}</dd></div>
            @if($product->author)<div><dt>@icon('pen-tool')Author</dt><dd>{{ $product->author }}</dd></div>@endif
            @if($product->published_at)<div><dt>@icon('calendar')Published</dt><dd>{{ $product->published_at->toFormattedDateString() }}</dd></div>@endif
        </dl>
    </div>
</section>
