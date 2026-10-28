<article class="border-bottom pb-4 mb-4">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
        <span class="small">{{ $review->reviewer ?: 'Anonymous' }}</span>
        <time class="small text-muted" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->toFormattedDateString() }}</time>
    </div>
    @include('shop.components.reviews.stars', ['rating' => $review->rating])
    @if($review->title)<h3 class="h6 mb-2">{{ rtrim($review->title, '. ') }}</h3>@endif
    @if($review->content)<p class="mb-0">{{ $review->content }}</p>@endif
</article>
