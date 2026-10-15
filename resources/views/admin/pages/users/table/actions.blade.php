<div class="d-flex justify-content-end align-items-center gap-2">
    <a href="{{ route('admin.users.show', $item) }}" class="btn btn-secondary btn-sm text-nowrap">
        @icon('square-pen', ['mr' => 2])Edit
    </a>
    <a href="{{ route('impersonate', $item) }}" class="btn btn-secondary btn-sm" title="Impersonate user" aria-label="Impersonate user" target="_blank" rel="noopener noreferrer">
        @icon('contact-round', ['mr' => 0])
    </a>
</div>
