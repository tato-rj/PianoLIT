<section id="reviews" class="border-top pt-3 mb-4" aria-labelledby="product-reviews-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="h5 mb-0" id="product-reviews-title">
            Customer reviews
            @if($reviewCount)
                <span class="text-muted">({{ $reviewCount }})</span>
            @endif
        </h4>
        @if(! $ownedReview)
            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#review-modal">@icon('square-pen')Write a review</button>
        @endif
    </div>
    <div class="row g-3 g-lg-4">
        <div class="col-md-4">
            @if($reviewCount)
            <div class="row gx-3">
                <div class="col-5">
                    <div class="d-flex flex-wrap align-items-baseline gap-1 mb-1"><span class="h4 mb-0">{{ $averageRating }}</span><span class="small text-muted">out of 5</span></div>
                    @include('shop.components.reviews.stars', ['rating' => $averageRating, 'sm' => true, 'mb' => 1])
                    <p class="small text-muted mb-0">Based on {{ $reviewCount }} {{ str_plural('review', $reviewCount) }}</p>
                </div>
                <div class="col-7">
                    @for($i = 5; $i >= 1; $i--)
                        @php($count = $reviews->where('rating', $i)->count())
                        <div class="d-flex align-items-center gap-2 small lh-sm {{ $i === 1 ? '' : 'mb-1' }}">
                            <span class="text-nowrap">{{ $i }} @icon('star', ['mr' => 0, 'color' => 'warning'])</span>
                            <div class="{{ ($storefront ?? false) ? 'shop-review-bar' : 'product-detail__rating-bar' }} flex-grow-1" aria-hidden="true"><span style="width: {{ $reviewCount ? $count / $reviewCount * 100 : 0 }}%"></span></div>
                            <span class="text-muted">{{ $count }}</span>
                        </div>
                    @endfor
                </div>
            </div>
            @else
            <p class="mb-1">No reviews yet</p>
            <p class="{{ ($storefront ?? false) ? '' : 'small' }} text-muted">Share your experience with other pianists.</p>
            @endif
        </div>
        <div class="col-md-8">
            @forelse($reviews->filter(function ($review) use ($ownedReview) { return $review->hasContent() || ($ownedReview && $ownedReview->is($review)); }) as $review)
                @include('shop.components.reviews.review')
            @empty
                @if($reviewCount)<p class="text-muted">{{ ($product instanceof \App\Merchandise\MerchandiseProduct) ? 'Customers' : 'Readers' }} have left ratings for this {{ $productKind }}, with no written reviews yet.</p>@endif
            @endforelse
        </div>
    </div>
</section>
@include('shop.components.reviews.form')
