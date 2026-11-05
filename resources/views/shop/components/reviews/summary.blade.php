@php($compact = $compact ?? false)
<a href="{{ $reviewUrl }}" class="d-inline-flex flex-wrap align-items-center gap-2 {{ $compact ? 'text-body text-decoration-none' : 'mb-4' }}" aria-label="{{ $reviewCount ? $averageRating.' out of 5, '.$reviewCount.' '.str_plural('review', $reviewCount) : 'No reviews yet' }}">
    @include('shop.components.reviews.stars', ['rating' => $averageRating, 'mb' => 0, 'sm' => false])
    <span class="{{ $compact ? 'small' : '' }}">{{ $reviewCount ? ($compact ? $averageRating.' ('.$reviewCount.')' : $averageRating.' · '.$reviewCount.' '.str_plural('review', $reviewCount)) : ($compact ? '(0)' : 'No reviews yet') }}</span>
</a>
