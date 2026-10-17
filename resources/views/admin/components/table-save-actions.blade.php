<div class="d-flex align-items-center justify-content-between gap-3 mb-4" data-table-save-actions>
    <div class="small text-muted d-inline-flex align-items-center gap-2" role="status" aria-live="polite">
        @icon('circle-dot', ['mr' => 0])
        <span data-save-status>{{ $status ?? 'All changes saved' }}</span>
    </div>
    <div class="ms-auto flex-shrink-0">{{ $slot }}</div>
</div>
