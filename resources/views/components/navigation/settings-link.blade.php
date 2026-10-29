<button type="button" class="btn btn-secondary w-100 d-flex align-items-center gap-3 text-start py-3" data-settings-go="{{ $target }}" @if($preview ?? false) data-ds-preview @endif aria-controls="settings-page-{{ $target }}">
    @icon($icon, ['mr' => 0])
    <span class="flex-grow-1 min-width-0">
        <span class="d-block">{{ $label }}</span>
        <span class="d-block text-muted fw-normal" data-settings-summary="{{ $target }}">{{ $summary }}</span>
    </span>
    @icon('chevron-right', ['mr' => 0])
</button>
