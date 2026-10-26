<div class="row gy-5 gx-4 gx-lg-5 editorial-layout">
    <div class="col-lg-8 col-xl-9 editorial-layout__content">{{ $slot }}</div>
    <aside class="col-lg-4 col-xl-3" aria-label="{{ $sidebarLabel ?? 'More from PianoLIT' }}">{{ $sidebar }}</aside>
</div>
