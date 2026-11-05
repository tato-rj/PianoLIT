@php
    $categoryArtwork = ['t-shirts' => 'peach', 'stickers' => 'sky', 'ebooks' => 'rose', 'escores' => 'cream', '' => null];
    $categoryLabels = \App\Services\Shop\Storefront::CATEGORIES;
    $selectedCategory = $category ?? '';
    $highlightCategory = $selectedCategory !== '' && array_key_exists($selectedCategory, $categoryArtwork);
@endphp
<nav class="shop-categories py-2" aria-label="Product categories" @if($preview ?? false) data-ds-preview @endif>
    @foreach($categoryArtwork as $key => $tone)
        @php($dimmed = $highlightCategory && $selectedCategory !== $key)
        <a href="{{ $categoryUrls[$key].(!empty($search) ? '?'.http_build_query(['q' => $search]) : '') }}"
           class="shop-category-link text-body text-decoration-none d-flex flex-column align-items-center gap-2 {{ $dimmed ? 'opacity-4' : '' }}"
           @if($selectedCategory === $key) aria-current="page" @endif>
            <span class="shop-category-icon rounded-circle border border-white border-4 {{ $dimmed ? 'shadow-none' : 'shadow-light' }} d-flex align-items-center justify-content-center"
                  @if($tone) style="--shop-category-surface: var(--pastel-{{ $tone }}-surface)" @endif>
                @include('shop.storefront.components.category-artwork', ['kind' => $key])
            </span>
            <span class="shop-category-label">{{ $key === '' ? 'View all' : $categoryLabels[$key] }}</span>
        </a>
    @endforeach
</nav>
