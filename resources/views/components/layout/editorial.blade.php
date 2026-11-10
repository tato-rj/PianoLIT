<div class="row gy-5 gx-4 gx-lg-5 editorial-layout">
    <div class="{{ ($showSidebar ?? true) ? 'col-lg-8 col-xl-9' : 'col-12' }} editorial-layout__content">{{ $slot }}</div>
    @if($showSidebar ?? true)
    <aside class="col-lg-4 col-xl-3" aria-label="{{ $sidebarLabel ?? 'More from PianoLIT' }}">{{ $sidebar }}</aside>
    @endif
</div>
