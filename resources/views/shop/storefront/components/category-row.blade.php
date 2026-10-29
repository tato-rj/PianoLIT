@php($sectionId = $sectionId ?? 'products-'.$categoryKey)
<section id="{{ $sectionId }}" class="col-12 {{ $halfWidth ? 'col-lg-6' : '' }} {{ !empty($offset) ? 'offset-lg-6' : '' }} mb-4" aria-labelledby="{{ $sectionId }}-title" data-shop-category>
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <h2 id="{{ $sectionId }}-title" class="shop-category-title mb-0"><a href="{{ $categoryUrl }}">{{ $categoryLabel }} @icon('arrow-right', ['mr' => 0])</a></h2>
        <div class="shop-category-controls d-flex gap-2" data-category-controls hidden>
            <button type="button" class="shop-button shop-button--compact" data-category-step="-1" aria-label="Previous {{ $categoryLabel }}" aria-controls="{{ $sectionId }}-list" disabled>@icon('arrow-left', ['mr' => 0])</button>
            <button type="button" class="shop-button shop-button--compact" data-category-step="1" aria-label="Next {{ $categoryLabel }}" aria-controls="{{ $sectionId }}-list" disabled>@icon('arrow-right', ['mr' => 0])</button>
        </div>
    </div>
    <div id="{{ $sectionId }}-list" class="shop-category-track {{ $halfWidth ? '' : 'shop-category-track--wide' }} d-flex" data-category-track tabindex="0" role="region" aria-label="{{ $categoryLabel }} products">
        @foreach($categoryProducts as $product)
            <div class="shop-category-item">@include('shop.storefront.components.card', ['cardHeading' => 'h3'])</div>
        @endforeach
    </div>
</section>
