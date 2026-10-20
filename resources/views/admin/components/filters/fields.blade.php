@foreach($fields ?? [] as $filterField)
    @php
        $filterFieldName = $filterField['name'];
        $filterFieldId = $filterField['id'] ?? $id.'-'.$filterFieldName;
        $filterFieldValue = $state[$filterFieldName] ?? $filterField['value'] ?? '';
    @endphp
    @if(($filterField['type'] ?? 'select') === 'select')
        @select(['bag' => 'default', 'id' => $filterFieldId, 'name' => $filterFieldName, 'label' => $filterField['label'], 'choices' => $filterField['options'], 'value' => $filterFieldValue, 'required' => false, 'grid' => $filterField['class'] ?? null, 'designSystemPreview' => $filterField['designSystemPreview'] ?? false, 'disabled' => $filterField['disabled'] ?? false, 'submitOnChange' => $filterField['submitOnChange'] ?? false])
    @else
        @input(['bag' => 'default', 'id' => $filterFieldId, 'name' => $filterFieldName, 'type' => $filterField['type'], 'label' => $filterField['label'], 'value' => $filterFieldValue, 'placeholder' => $filterField['placeholder'] ?? '', 'limit' => $filterField['type'] === 'number' ? ($filterField['max'] ?? null) : ($filterField['maxlength'] ?? 255), 'min' => $filterField['min'] ?? 0, 'step' => $filterField['step'] ?? null, 'required' => !empty($filterField['required']) ? 'required' : false, 'grid' => (($filterField['type'] === 'search') ? 'admin-filter-search ' : '').($filterField['class'] ?? ''), 'designSystemPreview' => $filterField['designSystemPreview'] ?? false, 'disabled' => $filterField['disabled'] ?? false])
    @endif
@endforeach
