<button type="button" class="btn btn-secondary rounded-circle shadow p-0 d-inline-flex align-items-center justify-content-center position-absolute bottom-0 end-0 m-3"
    style="width: 56px; height: 56px; --radius-button: var(--radius-circle)"
    data-book-launcher data-bs-toggle="modal" data-bs-target="#{{ $previewId }}"
    aria-label="Look inside" aria-haspopup="dialog" aria-controls="{{ $previewId }}" aria-describedby="{{ $previewId }}-page-count">
    @icon('book-open', ['mr' => 0, 'styles' => 'font-size: 24px'])
</button>
