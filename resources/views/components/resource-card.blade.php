<a href="{{ $url }}" class="resource-card card card-lift rounded border-0 shadow-light h-100 p-3" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-description" @if($preview ?? false) data-ds-preview @endif>
    <div class="resource-card__art pastel-card rounded-sm p-3" data-pastel-color="{{ $color ?? 'mint' }}" aria-hidden="true">{{ $artwork ?? '' }}</div>
    <div class="resource-card__body pt-3 px-1 pb-1">
        <p class="text-muted small mb-2">{{ $format }}</p>
        <h3 id="{{ $id }}-title" class="h5 mb-2">{{ $title }}</h3>
        <p id="{{ $id }}-description" class="resource-card__description text-muted mb-4">{{ $description }}</p>
        <span class="resource-card__action small fw-semibold d-inline-flex align-items-center gap-2 mt-auto">{{ $action }} @icon('arrow-right', ['mr' => 0])</span>
    </div>
</a>
