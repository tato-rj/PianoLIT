<div class="d-flex justify-content-end align-items-center gap-2">
    <a href="mailto:{{ $item->email }}" class="btn btn-light btn-sm" title="Contact subscriber" aria-label="Contact {{ $item->email }}">@icon('mail', ['mr' => 0])</a>
    <button type="button" class="btn btn-light btn-sm text-danger" data-url="{{ route('admin.subscriptions.destroy', ['subscription' => $item] + ($returnFilters ?? [])) }}" data-bs-toggle="modal" data-bs-target="#delete-modal" title="Delete subscriber" aria-label="Delete {{ $item->email }}">@icon('trash-2', ['mr' => 0])</button>
</div>
