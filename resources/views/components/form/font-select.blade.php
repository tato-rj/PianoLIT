@php
    $fontId = $id ?? 'number-font';
    $fontChoices = [
        'nunito' => ['Nunito', 'Rounded sans serif · Default'],
        'outfit' => ['Outfit', 'Geometric sans serif'],
        'playfair' => ['Playfair Display', 'Classic serif'],
        'pattaya' => ['Pattaya', 'Handwritten'],
        'sail' => ['Sail', 'Stylized'],
    ];
    $fontValue = isset($fontChoices[$value ?? '']) ? $value : 'nunito';
@endphp
<div class="font-select dropdown" data-font-select @if($previewSource ?? false) data-font-preview-source="{{ $previewSource }}" @endif>
    <label class="h6 mb-2" id="{{ $fontId }}-label" for="{{ $fontId }}-toggle">{{ $label ?? 'Number font' }}</label>
    <div hidden>
        @select(['bag' => 'default', 'id' => $fontId, 'name' => $fontId, 'label' => null, 'grid' => 'mb-0', 'choices' => collect($fontChoices)->map(fn ($font) => $font[0])->all(), 'value' => $fontValue, 'required' => false])
    </div>
    <button type="button" class="form-control form-control-select-trigger text-start" id="{{ $fontId }}-toggle" data-font-toggle data-bs-toggle="dropdown" aria-haspopup="menu" aria-expanded="false" aria-controls="{{ $fontId }}-menu" aria-labelledby="{{ $fontId }}-label {{ $fontId }}-value" @if($preview ?? false) data-ds-preview @endif>
        <span class="d-flex align-items-center justify-content-between gap-3">
            <span class="text-truncate" id="{{ $fontId }}-value" data-font-label>{{ $fontChoices[$fontValue][0] }}</span>
            <span class="font-select__preview font-select__preview--current" data-number-font="{{ $fontValue }}" data-font-current aria-hidden="true">120</span>
        </span>
    </button>
    <div class="dropdown-menu w-100 p-2" id="{{ $fontId }}-menu" role="menu" aria-labelledby="{{ $fontId }}-label">
        <div class="font-select__options" data-font-options>
            @foreach($fontChoices as $fontKey => [$fontName, $fontDescription])
                <button type="button" class="dropdown-item d-flex align-items-center justify-content-between gap-3 p-2{{ $fontValue === $fontKey ? ' active' : '' }}" data-font-value="{{ $fontKey }}" role="menuitemradio" aria-checked="{{ $fontValue === $fontKey ? 'true' : 'false' }}">
                    <span class="font-select__description">
                        <span class="d-block" data-font-name>{{ $fontName }}</span>
                        <small class="d-block opacity-75">{{ $fontDescription }}</small>
                    </span>
                    <span class="font-select__preview" data-number-font="{{ $fontKey }}" data-font-sample aria-hidden="true">120</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
