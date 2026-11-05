<article class="border-bottom pb-3 mb-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center gap-1 mb-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="small">{{ $review->reviewer ?: 'Anonymous' }}</span>
            @include('shop.components.reviews.stars', ['rating' => $review->rating, 'sm' => true, 'mb' => 0])
        </div>
        <time class="small text-muted" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->toFormattedDateString() }}</time>
    </div>
    @if($review->title)<h3 class="h6 mb-1">{{ rtrim($review->title, '. ') }}</h3>@endif
    @if($review->content)<p class="mb-0">{{ $review->content }}</p>@endif
    @if($ownedReview && $ownedReview->is($review))
        <div class="d-flex justify-content-end mt-2">
            @include('shop.components.reviews.actions.delete')
        </div>
    @endif
</article>
