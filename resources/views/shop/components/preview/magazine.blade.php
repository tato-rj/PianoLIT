@php
    $previewPages = $previewPages ?? $product->previews;
    $previewId = $previewId ?? 'preview-product';
    $previewTitle = $previewTitle ?? $product->title;
@endphp
@if($previewPages)
@component('components.dialogs.fullscreen.panel', [
    'id' => $previewId,
    'headingId' => $previewId.'-title',
    'classes' => 'product-preview-dialog',
    'transparent' => true,
    'floatingClose' => true,
    'closeLabel' => 'Close book preview',
])
    <h2 class="visually-hidden" id="{{ $previewId }}-title">{{ $previewTitle }} · Preview</h2>
    <div class="product-preview" data-book-preview>
        <div class="product-preview__stage" data-book-stage role="group" aria-label="Book preview. Drag a page corner or use the left and right arrow keys to turn pages" aria-busy="true">
            <p class="bg-white rounded px-3 py-2 mb-0" data-book-loading role="status">Loading preview…</p>
            <div data-book-host hidden>
                <div class="product-preview__book" data-book>
                    @foreach($previewPages as $preview)
                    <div class="product-preview__page">
                        <img data-preview-src="{{ !empty($previewUrls) ? $preview : (($storefront ?? false) ? asset('storage/'.$preview) : storage($preview)) }}" alt="Preview page {{ $loop->iteration }} of {{ count($previewPages) }}" draggable="false">
                        <span class="product-preview__missing text-muted" hidden>This page could not be loaded.</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="product-preview__controls d-flex align-items-center justify-content-center gap-2" @if(count($previewPages) < 2) hidden @endif>
            <button type="button" class="btn btn-secondary rounded-circle" style="--radius-button: var(--radius-circle)" data-book-previous aria-label="Previous preview page" disabled>@icon('chevron-left', ['mr' => 0])</button>
            <span class="visually-hidden" data-book-counter aria-live="polite" aria-atomic="true"></span>
            <button type="button" class="btn btn-secondary rounded-circle" style="--radius-button: var(--radius-circle)" data-book-next aria-label="Next preview page" disabled>@icon('chevron-right', ['mr' => 0])</button>
        </div>
        <div class="product-preview__error" data-book-error hidden>
            @component('components.feedback.alert', ['color' => 'red'])
            @slot('message')
                Some preview pages could not be loaded.
                <button type="button" class="btn btn-secondary btn-sm ms-2" data-book-retry>Try again</button>
            @endslot
            @endcomponent
        </div>
    </div>
@endcomponent
@endif
