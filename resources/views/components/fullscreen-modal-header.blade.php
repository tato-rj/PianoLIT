<header class="fullscreen-modal-header {{ $headerClasses ?? '' }}">
    <div><h1 class="h3 mb-1" id="{{ $headingId }}">{{ $title }}</h1><p class="text-muted mb-0">{{ $subtitle }}</p></div>
    <button class="btn btn-secondary fullscreen-modal-close {{ $closeClasses ?? '' }}" type="button" data-bs-dismiss="modal" aria-label="{{ $closeLabel }}">@icon('x', ['mr' => 0])</button>
</header>
