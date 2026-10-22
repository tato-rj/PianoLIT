<div class="d-flex align-items-center gap-2 text-muted small mb-2">
    <span>{{ ucfirst($title) }}</span>
    <button type="button" class="btn-raw text-muted" aria-label="About {{ $title }}" data-bs-toggle="modal" data-bs-target="#modal-{{ str_slug($title) }}">@icon('circle-help', ['mr' => 0])</button>
</div>
