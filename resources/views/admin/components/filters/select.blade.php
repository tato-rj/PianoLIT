<label class="admin-filter-label">{{ $label }}
    <select class="admin-filter-select" name="{{ $name }}" @if(!empty($designSystemPreview)) data-ds-preview @endif>
        @foreach($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @if((string) $value === (string) $optionValue) selected @endif>{{ $optionLabel }}</option>
        @endforeach
    </select>
</label>
