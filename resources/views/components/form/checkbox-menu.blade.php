@php
    $menuId = 'checkbox-menu-'.\Illuminate\Support\Str::uuid();
    $menuSelected = array_intersect(array_keys($options), $selected ?? []);
    $menuAllLabel = $allLabel ?? 'All options';
    $menuSummary = count($menuSelected) === count($options) ? $menuAllLabel : implode(' + ', array_intersect_key($options, array_flip($menuSelected)));
    $menuError = $errors->first($name);
@endphp
<div class="checkbox-menu dropdown" data-checkbox-menu data-all-label="{{ $menuAllLabel }}">
    @include('components.form.label', ['labelTag' => 'span', 'labelId' => $menuId.'-label'])
    <button type="button" class="form-control admin-filter-select" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-controls="{{ $menuId }}-options" aria-labelledby="{{ $menuId }}-label {{ $menuId }}-summary" @if(!empty($designSystemPreview)) data-ds-preview @endif>
        <span id="{{ $menuId }}-summary" data-checkbox-menu-summary>{{ $menuSummary ?: 'Choose options' }}</span>
    </button>
    <div class="dropdown-menu p-2" id="{{ $menuId }}-options" role="group" aria-labelledby="{{ $menuId }}-label">
        <input type="hidden" name="{{ $name }}[]" value="">
        <button type="button" class="dropdown-item" data-checkbox-menu-all>Select all</button>
        <div class="dropdown-divider"></div>
        @foreach($options as $value => $optionLabel)
        <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
            <input class="form-check-input m-0 flex-shrink-0" type="checkbox" name="{{ $name }}[]" value="{{ $value }}" data-checkbox-menu-option data-label="{{ $optionLabel }}" @if(in_array($value, $menuSelected, true)) checked @endif aria-errormessage="{{ $menuId }}-error" @if($menuError) aria-invalid="true" @endif>
            <span>{{ $optionLabel }}</span>
        </label>
        @endforeach
        <p class="text-danger small mb-0 px-3" id="{{ $menuId }}-error" data-checkbox-menu-error role="alert" @if(!$menuError) hidden @endif>{{ $menuError ?: 'Choose at least one option.' }}</p>
    </div>
</div>
