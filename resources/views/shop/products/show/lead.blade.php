<div class="row g-4 g-lg-5 align-items-center product-detail__hero mb-5">
    <div class="col-md-5">
        <div class="product-detail__artwork">
            @if($product->previews)
            <button type="button" class="btn-raw product-detail__cover" data-bs-toggle="modal" data-bs-target="#preview-product" aria-label="Preview {{ $product->title }}">
                <img src="{{ $product->mockup_image() ?: $product->cover_image() }}" alt="{{ $product->title }} cover" fetchpriority="high">
            </button>
            @else
            <div class="product-detail__cover">
                <img src="{{ $product->mockup_image() ?: $product->cover_image() }}" alt="{{ $product->title }} cover" fetchpriority="high">
            </div>
            @endif
        </div>
        @if($product->previews)
        <div class="text-center mt-3">
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#preview-product">@icon('book-open')Look inside</button>
            <p class="small text-muted mt-2 mb-0">Flip through {{ count($product->previews) }} preview pages</p>
        </div>
        @endif
    </div>
    <div class="col-md-7">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="small text-muted">PianoLIT {{ $productKind }} · Digital edition</span>
            @topics(['model' => $product])
        </div>
        <h1 class="h2 mb-3">{{ rtrim($product->title, '. ') }}</h1>
        <p class="text-muted mb-3">{{ $product->subtitle }}</p>
        <a href="#reviews" class="product-detail__rating d-inline-flex align-items-center gap-2 mb-4" aria-label="{{ $reviewCount ? $averageRating.' out of 5, '.$reviewCount.' reviews' : 'No reviews yet' }}">
            @include('shop.components.reviews.stars', ['rating' => $averageRating, 'mb' => 0])
            <span class="small">{{ $reviewCount ? $averageRating.' · '.$reviewCount.' '.str_plural('review', $reviewCount) : 'Be the first to review' }}</span>
        </a>
        <div class="product-detail__purchase py-3 border-top border-bottom">
            <div class="d-flex align-items-baseline flex-wrap gap-2 mb-3">
                @include('shop.components.price', ['free' => auth()->check() && auth()->user()->isEligibleForFreeMonthlyProduct(), 'showLabel' => false])
                <span class="small text-muted">{{ implode(' + ', array_keys($product->links())) }} download</span>
            </div>
            @include('shop.components.actions.download')
            @include('shop.components.actions.audio')
            <p class="small text-muted mt-2 mb-0">@icon('check', ['color' => 'green'])Instant digital download · Lifetime access</p>
        </div>
        <div class="product-detail__membership mt-3">
            @include('shop.components.loyalty.notification', ['compact' => true])
        </div>
    </div>
</div>
