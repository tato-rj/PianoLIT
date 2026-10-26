<input type="color" class="form-control form-control-color" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
    @isset($dataAttribute) data-{{ $dataAttribute }} @endisset
    @if($designSystemPreview ?? false) data-ds-preview @endif>
