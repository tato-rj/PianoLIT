<button type="button" class="btn-raw p-2 d-inline-flex align-items-center justify-content-center {{ ($inheritColor ?? false) ? 'text-reset' : 'text-dark' }}"
        aria-label="Open hub menu" title="Explore PianoLIT"
        @if($designSystemPreview ?? false) data-ds-preview @else data-bs-toggle="offcanvas" data-bs-target="#public-hub-menu" aria-controls="public-hub-menu" @endif>
    @icon('menu', ['mr' => 0, 'styles' => 'font-size: 1.5rem'])
</button>
