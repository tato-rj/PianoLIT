@php($fileErrors = $errors->getBag($bag ?? 'default'))
<div class="form-group {{ $grid ?? '' }}">
    @include('components.forms.label')
    <input type="file" class="form-control {{ validate($fileErrors, $name) }}" id="{{ $id }}" name="{{ $name }}"
        @if($multiple ?? false) multiple @endif
        @if($required ?? false) required @endif @if($disabled ?? false) disabled @endif
        @isset($accept) accept="{{ $accept }}" @endisset
        @if($fileErrors->has($name)) aria-invalid="true" @endif
        @if($designSystemPreview ?? false) data-ds-preview @endif>
    @if(!empty($currentUrl))
    <a class="small d-inline-block mt-2" href="{{ $currentUrl }}" target="_blank" rel="noopener noreferrer">@icon('external-link', ['classes' => 'icon-size-xs']){{ $currentLabel ?? 'Open current file' }}</a>
    @endif
    @include('components.forms.error', ['bag' => $bag ?? 'default', 'field' => $name])
</div>
