<div class="shop-gallery" data-shop-gallery aria-label="Product images">
    @forelse($product['gallery'] as $image)
        <div data-gallery-slide @if(!$loop->first) hidden @endif>
            @if($product['hasPreview'])
                <button type="button" class="shop-gallery__preview" data-bs-toggle="modal" data-bs-target="#preview-product" aria-label="Preview {{ $product['name'] }}">
            @endif
            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="800" height="800" @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif>
            @if($product['hasPreview'])</button>@endif
        </div>
    @empty
        <div class="shop-image-placeholder">@icon('image')<span>Image coming soon</span></div>
    @endforelse
    @if($product['gallery']->count() > 1)
        <button class="shop-gallery__arrow shop-gallery__arrow--prev" type="button" data-gallery-step="-1" aria-label="Previous image">@icon('arrow-left')</button>
        <button class="shop-gallery__arrow shop-gallery__arrow--next" type="button" data-gallery-step="1" aria-label="Next image">@icon('arrow-right')</button>
    @endif
</div>
@if($product['gallery']->count() > 1)
    <p class="shop-gallery-count mt-2 mb-0" aria-live="polite" data-gallery-count>1 / {{ $product['gallery']->count() }}</p>
@endif
@if($product['hasPreview'])
    <div class="mt-3">
        <button type="button" class="shop-button w-100" data-bs-toggle="modal" data-bs-target="#preview-product">@icon('book-open')Look inside</button>
        <p class="text-muted mt-2 mb-0">Flip through {{ count($digitalProduct->previews) }} preview pages</p>
    </div>
@endif
