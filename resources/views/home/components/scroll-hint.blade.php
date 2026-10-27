<div class="project-home__scroll-hint" data-home-scroll-hint hidden>
    <button type="button" class="btn btn-secondary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center shadow text-black" style="--radius-button:var(--radius-circle)" aria-label="Scroll down to explore more PianoLIT worlds">
        @icon('arrow-down', ['mr' => 0, 'styles' => 'color:var(--color-black) !important;'])
    </button>
</div>
@push('scripts')
    <script src="{{ asset(mix('js/components/home-scroll-hint.js')) }}" defer></script>
@endpush
