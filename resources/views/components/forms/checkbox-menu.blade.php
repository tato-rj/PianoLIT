@php
    $menuId = 'checkbox-menu-'.\Illuminate\Support\Str::uuid();
    $menuSelected = array_intersect(array_keys($options), $selected ?? []);
    $menuAllLabel = $allLabel ?? 'All options';
    $menuAllowEmpty = $allowEmpty ?? false;
    $menuSummary = $menuAllowEmpty
        ? (count($menuSelected) ? count($menuSelected).' selected' : 'None selected')
        : (count($menuSelected) === count($options) ? $menuAllLabel : implode(' + ', array_intersect_key($options, array_flip($menuSelected))));
    $menuError = $errors->first($name);
@endphp
<div class="checkbox-menu dropdown" data-checkbox-menu data-all-label="{{ $menuAllLabel }}" @if($menuAllowEmpty) data-allow-empty @endif>
    @include('components.forms.label', ['labelTag' => 'span', 'labelId' => $menuId.'-label'])
    <button type="button" class="form-control admin-filter-select" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-controls="{{ $menuId }}-options" aria-labelledby="{{ $menuId }}-label {{ $menuId }}-summary" @if(!empty($designSystemPreview)) data-ds-preview @endif>
        <span id="{{ $menuId }}-summary" data-checkbox-menu-summary>{{ $menuSummary ?: 'Choose options' }}</span>
    </button>
    <div class="dropdown-menu p-2{{ $menuAllowEmpty ? ' dropdown-menu-end dropdown-menu-sm-start' : '' }}" id="{{ $menuId }}-options" role="group" aria-labelledby="{{ $menuId }}-label"
         @if(!empty($description)) aria-describedby="{{ $menuId }}-description" @endif>
        @unless($menuAllowEmpty)
            <input type="hidden" name="{{ $name }}[]" value="">
            <button type="button" class="dropdown-item" data-checkbox-menu-all>Select all</button>
            <div class="dropdown-divider"></div>
        @endunless
        @foreach($options as $value => $optionLabel)
        <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
            <input class="form-check-input m-0 flex-shrink-0" type="checkbox" name="{{ $name }}[]" value="{{ $value }}" data-checkbox-menu-option data-label="{{ $optionLabel }}" @if(in_array($value, $menuSelected, true)) checked @endif aria-errormessage="{{ $menuId }}-error" @if($menuError) aria-invalid="true" @endif>
            <span class="text-nowrap">{{ $optionLabel }}</span>
        </label>
        @endforeach
        @if(!empty($description))
            <p class="small text-muted mb-0 mt-2 px-3" id="{{ $menuId }}-description">{{ $description }}</p>
        @endif
        <p class="text-danger small mb-0 px-3" id="{{ $menuId }}-error" data-checkbox-menu-error role="alert" @if(!$menuError) hidden @endif>{{ $menuError ?: 'Choose at least one option.' }}</p>
    </div>
</div>
