@php($compact = $compact ?? false)
<a href="{{ $reviewUrl }}" class="d-inline-flex flex-wrap align-items-center gap-2 text-body text-decoration-none {{ $compact ? '' : 'mb-4' }}" aria-label="{{ $reviewCount ? $averageRating.' out of 5, '.$reviewCount.' '.str_plural('review', $reviewCount) : 'No reviews yet' }}">
    @include('shop.components.reviews.stars', ['rating' => $averageRating, 'mb' => 0, 'sm' => false])
    @if($reviewCount)
        <span class="{{ $compact ? 'small' : '' }}">({{ $reviewCount }})</span>
    @else
        <span class="small text-muted fst-italic">No reviews yet</span>
    @endif
</a>
