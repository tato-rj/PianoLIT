<header class="fullscreen-modal-header {{ $headerClasses ?? '' }}">
    @isset($headerContent)
    {{ $headerContent }}
    @else
    <div><h1 class="h3 mb-1" id="{{ $headingId }}">{{ $title }}</h1><p class="text-muted mb-0">{{ $subtitle }}</p></div>
    @endisset
    @include('components.fullscreen-modal-close')
</header>
