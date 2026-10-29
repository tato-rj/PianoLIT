<section id="reviews" class="{{ ($storefront ?? false) ? 'shop-product-section' : 'border-top' }} pt-4 mb-5" aria-labelledby="product-reviews-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <h2 class="{{ ($storefront ?? false) ? '' : 'h5' }} mb-0" id="product-reviews-title">Customer reviews <span class="text-muted">({{ $reviewCount }})</span></h2>
        @if(! $ownedReview)
            <button type="button" class="{{ ($storefront ?? false) ? 'shop-button' : 'btn btn-secondary' }}" data-bs-toggle="modal" data-bs-target="#review-modal">@icon('square-pen')Write a review</button>
        @endif
    </div>
    <div class="row g-4 g-lg-5">
        <div class="col-md-4">
            @if($reviewCount)
            <div class="d-flex align-items-baseline gap-2 mb-2"><span class="h2 mb-0">{{ $averageRating }}</span><span class="text-muted">out of 5</span></div>
            @include('shop.components.reviews.stars', ['rating' => $averageRating])
            <p class="{{ ($storefront ?? false) ? '' : 'small' }} text-muted mb-3">Based on {{ $reviewCount }} {{ str_plural('review', $reviewCount) }}</p>
            @for($i = 5; $i >= 1; $i--)
                @php($count = $reviews->where('rating', $i)->count())
                <div class="d-flex align-items-center gap-2 mb-2 {{ ($storefront ?? false) ? '' : 'small' }}">
                    <span class="text-nowrap">{{ $i }} @icon('star', ['mr' => 0, 'color' => 'warning'])</span>
                    <div class="{{ ($storefront ?? false) ? 'shop-review-bar' : 'product-detail__rating-bar' }} flex-grow-1" aria-hidden="true"><span style="width: {{ $reviewCount ? $count / $reviewCount * 100 : 0 }}%"></span></div>
                    <span class="text-muted">{{ $count }}</span>
                </div>
            @endfor
            @else
            <p class="mb-1">No reviews yet</p>
            <p class="{{ ($storefront ?? false) ? '' : 'small' }} text-muted">Share your experience with other pianists.</p>
            @endif
            @if($ownedReview)
                <div class="mt-4">
                @if($ownedReview->isPublished())
                    @include('shop.components.reviews.actions.delete', ['review' => $ownedReview])
                @else
                    @include('shop.components.reviews.actions.pending', ['review' => $ownedReview])
                @endif
                </div>
            @endif
        </div>
        <div class="col-md-8">
            @forelse($reviews->filter(function ($review) { return $review->hasContent(); }) as $review)
                @include('shop.components.reviews.review')
            @empty
                @if($reviewCount)<p class="text-muted">Readers have left ratings for this {{ $productKind }}, with no written reviews yet.</p>@endif
            @endforelse
        </div>
    </div>
</section>
@include('shop.components.reviews.form')
