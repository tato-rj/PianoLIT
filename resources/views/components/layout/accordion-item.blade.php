<div class="accordion-item">
    <h3 class="accordion-header" id="{{ $id }}-heading">
        <button class="accordion-button {{ ($open ?? false) ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ ($open ?? false) ? 'true' : 'false' }}" aria-controls="{{ $id }}">
            {{ $title }}
            @icon('plus', ['mr' => 0, 'classes' => 'accordion-toggle-icon'])
        </button>
    </h3>
    <div id="{{ $id }}" class="accordion-collapse collapse {{ ($open ?? false) ? 'show' : '' }}" aria-labelledby="{{ $id }}-heading" @isset($parentId) data-bs-parent="#{{ $parentId }}" @endisset>
        <div class="accordion-body">{{ $slot }}</div>
    </div>
</div>
