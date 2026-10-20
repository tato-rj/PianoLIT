<div class="d-flex flex-wrap align-items-center gap-3 mb-4" data-table-bulk-delete data-table-id="{{ $tableId }}" data-form-id="{{ $id }}-form">
    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="{{ $id }}-all" data-bulk-select-all>
        <label class="form-check-label" for="{{ $id }}-all">Select this page</label>
    </div>
    <span class="text-muted" role="status" data-bulk-count>0 selected</span>
    <button type="button" class="btn btn-sm btn-danger" data-bulk-open disabled data-bs-toggle="modal" data-bs-target="#{{ $id }}-modal">@icon('trash-2')Delete selected</button>
</div>
@component('components.modal', ['id' => $id.'-modal', 'header' => 'Delete selected '.e($label), 'centered' => true])
    @slot('body')
        <p>Delete <strong data-bulk-confirm-count="{{ $id }}-form">0</strong> selected {{ $label }}? {{ $deleteDescription ?? 'This also removes them from email lists.' }} This action cannot be undone.</p>
        @if($preview ?? false)
        <div id="{{ $id }}-form" data-bulk-preview>
        @else
        <form method="POST" action="{{ $action }}" id="{{ $id }}-form">
            @csrf
            @method('DELETE')
        @endif
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="{{ ($preview ?? false) ? 'button' : 'submit' }}" class="btn btn-danger" data-bulk-submit disabled>Delete selected</button>
            </div>
        @if($preview ?? false)</div>@else</form>@endif
    @endslot
@endcomponent
