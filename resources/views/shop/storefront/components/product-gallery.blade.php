@php
    $usesColorGallery = array_key_exists('galleryColor', $product);
    $galleryImages = $product['gallery']->values();
    $visibleGallery = $galleryImages->filter(fn ($image) => !$usesColorGallery || $image['color'] === $product['galleryColor']);
    $visibleCount = $visibleGallery->count();
    $firstGalleryIndex = $visibleGallery->keys()->first();
    $thumbnailViews = [];
    $galleryPreviewId = $previewId ?? 'preview-product';
@endphp
<div class="shop-gallery-layout" data-shop-gallery @if($usesColorGallery) data-gallery-product="{{ $product['id'] }}" data-gallery-selected-color="{{ $product['galleryColor'] }}" @endif aria-label="Product images">
    <div class="shop-gallery border overflow-hidden">
        @foreach($galleryImages as $image)
            <div data-gallery-slide @if($usesColorGallery) data-gallery-color="{{ $image['color'] }}" @endif @if($loop->index !== $firstGalleryIndex) hidden @endif>
                @if($product['hasPreview'])
                    <button type="button" class="shop-gallery__preview" data-bs-toggle="modal" data-bs-target="#{{ $galleryPreviewId }}" aria-label="Preview {{ $product['name'] }}">
                @endif
                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="800" height="800" @if($loop->index !== $firstGalleryIndex) loading="lazy" @else fetchpriority="high" @endif>
                @if($product['hasPreview'])</button>@endif
            </div>
        @endforeach
        <div class="shop-image-placeholder" data-gallery-empty @if($visibleCount) hidden @endif>@icon('image')<span>{{ $usesColorGallery ? 'No images for this color' : 'Image coming soon' }}</span></div>
        @if($product['hasPreview'])
            @include('shop.components.preview.trigger', ['previewId' => $galleryPreviewId])
        @endif
    </div>
    @if($galleryImages->count() > 1)
        <div class="shop-gallery__thumbnails order-first" data-gallery-thumbnails role="group" aria-label="Choose a product image" @if($visibleCount < 2) hidden @endif>
        <div class="d-flex flex-column gap-2 position-absolute top-0 bottom-0 start-0 end-0 overflow-auto">
        @foreach($galleryImages as $image)
            @php
                $thumbnailColor = $usesColorGallery ? $image['color'] : '';
                $thumbnailViews[$thumbnailColor] = ($thumbnailViews[$thumbnailColor] ?? 0) + 1;
                $thumbnailSelected = $loop->index === $firstGalleryIndex;
            @endphp
            <button type="button" class="shop-gallery__thumbnail btn btn-secondary p-1 shadow-none rounded-0 {{ $thumbnailSelected ? 'border-dark' : '' }}" data-gallery-thumb="{{ $loop->index }}" aria-label="View image {{ $thumbnailViews[$thumbnailColor] }}" aria-pressed="{{ $thumbnailSelected ? 'true' : 'false' }}" @if($usesColorGallery && $image['color'] !== $product['galleryColor']) hidden @endif>
                <img src="{{ $image['url'] }}" alt="" width="64" height="64" loading="lazy">
            </button>
        @endforeach
        </div>
        </div>
        <p class="visually-hidden" aria-live="polite" data-gallery-count @if($visibleCount < 2) hidden @endif>Image {{ $visibleCount ? 1 : 0 }} of {{ $visibleCount }}</p>
    @endif
</div>
@if($product['hasPreview'])
    <p class="visually-hidden" id="{{ $galleryPreviewId }}-page-count">View {{ count($previewPages) }} preview {{ count($previewPages) === 1 ? 'page' : 'pages' }}</p>
@endif
