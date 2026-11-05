@php
    $usesColorGallery = array_key_exists('galleryColor', $product);
    $visibleGallery = $product['gallery']->filter(fn ($image) => !$usesColorGallery || $image['color'] === $product['galleryColor']);
    $visibleCount = $visibleGallery->count();
    $firstGalleryIndex = $visibleGallery->keys()->first();
@endphp
<div class="shop-gallery border rounded-sm overflow-hidden" data-shop-gallery @if($usesColorGallery) data-gallery-product="{{ $product['id'] }}" data-gallery-selected-color="{{ $product['galleryColor'] }}" @endif aria-label="Product images">
    @foreach($product['gallery'] as $image)
        <div data-gallery-slide @if($usesColorGallery) data-gallery-color="{{ $image['color'] }}" @endif @if($loop->index !== $firstGalleryIndex) hidden @endif>
            @if($product['hasPreview'])
                <button type="button" class="shop-gallery__preview" data-bs-toggle="modal" data-bs-target="#preview-product" aria-label="Preview {{ $product['name'] }}">
            @endif
            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="800" height="800" @if($loop->index !== $firstGalleryIndex) loading="lazy" @else fetchpriority="high" @endif>
            @if($product['hasPreview'])</button>@endif
        </div>
    @endforeach
    <div class="shop-image-placeholder" data-gallery-empty @if($visibleCount) hidden @endif>@icon('image')<span>{{ $usesColorGallery ? 'No images for this color' : 'Image coming soon' }}</span></div>
    @if($product['gallery']->count() > 1)
        <button class="btn btn-secondary rounded-circle shop-gallery__arrow shop-gallery__arrow--prev" type="button" data-gallery-step="-1" @if($visibleCount < 2) hidden @endif aria-label="Previous image">@icon('arrow-left', ['mr' => 0])</button>
        <button class="btn btn-secondary rounded-circle shop-gallery__arrow shop-gallery__arrow--next" type="button" data-gallery-step="1" @if($visibleCount < 2) hidden @endif aria-label="Next image">@icon('arrow-right', ['mr' => 0])</button>
    @endif
</div>
@if($product['gallery']->count() > 1)
    <p class="shop-gallery-count mt-2 mb-0" aria-live="polite" data-gallery-count @if($visibleCount < 2) hidden @endif>{{ $visibleCount ? 1 : 0 }} / {{ $visibleCount }}</p>
@endif
@if($product['hasPreview'])
    <div class="mt-3">
        <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#preview-product">@icon('book-open')Look inside</button>
        <p class="text-muted mt-2 mb-0">Flip through {{ count($digitalProduct->previews) }} preview pages</p>
    </div>
@endif
