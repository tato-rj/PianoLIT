<a href="#reviews" class="d-inline-flex flex-wrap align-items-center gap-2 mb-4" aria-label="{{ $reviewCount ? $averageRating.' out of 5, '.$reviewCount.' reviews' : 'No reviews yet' }}">
    @include('shop.components.reviews.stars', ['rating' => $averageRating, 'mb' => 0])
    <span>{{ $reviewCount ? $averageRating.' · '.$reviewCount.' '.str_plural('review', $reviewCount) : 'Be the first to review' }}</span>
</a>
@include('shop.components.actions.download', ['actionClasses' => 'btn btn-primary w-100'])
@include('shop.components.actions.audio', ['actionClasses' => 'btn btn-secondary w-100'])
<p class="text-muted mt-3 mb-0">Instant digital download · Lifetime access</p>
<div class="mt-3">@include('shop.components.loyalty.notification', ['compact' => true])</div>
