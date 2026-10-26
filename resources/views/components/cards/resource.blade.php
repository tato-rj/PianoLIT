<a href="{{ $url }}" class="resource-card card card-lift h-100" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-description" @if($preview ?? false) data-ds-preview @endif>
    <div class="resource-card__art {{ $artworkClass ?? '' }}" aria-hidden="true">{{ $artwork ?? '' }}</div>
    <div class="resource-card__body p-4">
        <p class="resource-card__format mb-2">{{ $format }}</p>
        <h3 id="{{ $id }}-title" class="h5 mb-2">{{ $title }}</h3>
        <p id="{{ $id }}-description" class="resource-card__description text-muted mb-3">{{ $description }}</p>
        <span class="resource-card__action small fw-semibold d-inline-flex align-items-center gap-2 mt-auto">{{ $action }} @icon('arrow-right', ['mr' => 0])</span>
    </div>
</a>
