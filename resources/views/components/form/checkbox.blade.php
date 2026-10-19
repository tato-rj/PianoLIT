<div class="form-check {{ $classes ?? '' }}">
    <input class="form-check-input" type="{{ ($type ?? 'checkbox') === 'radio' ? 'radio' : 'checkbox' }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value ?? 'on' }}" @if($checked ?? false) checked @endif @if($disabled ?? false) disabled @endif @if($submitOnChange ?? false) onchange="this.form.submit()" @endif @if($designSystemPreview ?? false) data-ds-preview @endif>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
</div>
