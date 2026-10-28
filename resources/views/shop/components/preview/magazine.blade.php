@if($product->previews)
@component('components.dialogs.modal', [
    'id' => 'preview-product',
    'header' => 'Look inside',
    'options' => ['size' => 'xl'],
    'descriptionId' => 'product-preview-description',
])
    @slot('body')
        <p id="product-preview-description" class="text-muted small mb-3">{{ $product->title }} · Selected preview pages</p>
        <div class="product-preview" data-book-preview>
            <div class="product-preview__stage" data-book-stage tabindex="0" role="group" aria-label="Book preview. Use the left and right arrow keys to turn pages" aria-busy="true">
                <p class="text-muted mb-0" data-book-loading role="status">Opening your preview…</p>
                <div data-book-host hidden>
                    <div class="product-preview__book" data-book>
                        @foreach($product->previews as $preview)
                        <div class="product-preview__page">
                            <img data-preview-src="{{ storage($preview) }}" alt="Preview page {{ $loop->iteration }} of {{ count($product->previews) }}" draggable="false">
                            <span class="product-preview__missing small text-muted" hidden>This page couldn’t be loaded</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="product-preview__zoom" data-book-zoom hidden><img alt="" data-book-zoom-image></div>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-book-previous aria-label="Previous preview page" disabled>@icon('chevron-left', ['mr' => 0])</button>
                    <span class="small text-muted text-nowrap" data-book-counter aria-live="polite" aria-atomic="true"></span>
                    <button type="button" class="btn btn-secondary btn-sm" data-book-next aria-label="Next preview page" disabled>@icon('chevron-right', ['mr' => 0])</button>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-book-magnify aria-pressed="false" disabled>@icon('zoom-in')<span data-book-zoom-label>Zoom in</span></button>
            </div>
            <p class="small text-muted text-center mt-3 mb-2" data-book-hint>Drag a page corner or use the arrows to turn pages</p>
            <div class="product-preview__thumbnails d-flex gap-2 justify-content-center" aria-label="Go to preview page">
                @foreach($product->previews as $preview)
                <button type="button" class="btn btn-secondary btn-sm" data-book-page="{{ $loop->iteration }}" aria-label="Go to preview page {{ $loop->iteration }}">{{ $loop->iteration }}</button>
                @endforeach
            </div>
            <div class="small text-danger text-center mt-2" data-book-error role="status" hidden>
                Some pages couldn’t load. <button type="button" class="btn btn-secondary btn-sm" data-book-retry>Try again</button>
            </div>
        </div>
        <noscript><p class="mt-3">Enable JavaScript to turn pages, or open a sample below.</p>@foreach($product->previews as $preview)<a class="me-3" href="{{ storage($preview) }}" target="_blank" rel="noopener noreferrer">Preview page {{ $loop->iteration }}</a>@endforeach</noscript>
    @endslot
@endcomponent
@endif
