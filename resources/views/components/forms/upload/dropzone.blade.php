@php
    $uploadBag = $bag ?? 'default';
    $uploadErrors = $errors->getBag($uploadBag);
    $uploadField = str_replace('[]', '', $name);
    $uploadInvalid = ($showErrors ?? true) && ($uploadErrors->has($uploadField) || $uploadErrors->has($uploadField.'.*'));
@endphp
<div class="form-group {{ $grid ?? '' }}" data-file-dropzone>
    @include('components.forms.label')
    <div class="file-dropzone @if($uploadInvalid) is-invalid @endif @if($disabled ?? false) is-disabled @endif">
        <input type="file" class="file-dropzone__input" id="{{ $id }}" name="{{ $name }}"
            @if($multiple ?? false) multiple @endif
            @if($required ?? false) required @endif @if($disabled ?? false) disabled @endif
            @isset($accept) accept="{{ $accept }}" @endisset
            data-max-files="{{ $maxFiles ?? (($multiple ?? false) ? 10 : 1) }}" data-max-bytes="{{ $maxBytes ?? 10485760 }}"
            aria-describedby="{{ $id }}-hint {{ $id }}-selection" @if($uploadInvalid) aria-invalid="true" @endif
            @if($designSystemPreview ?? false) data-ds-preview @endif>
        <div class="file-dropzone__message" aria-hidden="true">
            @icon('upload', ['classes' => 'icon-size-lg', 'mr' => 0])
            <span>{{ ($multiple ?? false) ? 'Drag and drop images here' : 'Drag and drop an image here' }}</span>
            <span class="small text-muted">or click to select from your computer</span>
        </div>
        <div class="file-dropzone__previews" data-file-previews aria-label="Selected images" hidden></div>
    </div>
    <p class="small text-muted mt-2 mb-1" id="{{ $id }}-hint">{{ $hint ?? 'JPG, PNG or WebP · up to 10 MB per image' }}</p>
    <p class="small mb-0 text-break" id="{{ $id }}-selection" data-file-selection role="status" aria-live="polite">No files selected</p>
    @if($uploadInvalid)
        <span class="invalid-feedback d-block" role="alert">{{ $uploadErrors->first($uploadField) ?: $uploadErrors->first($uploadField.'.*') }}</span>
    @endif
</div>
@once
    <script src="{{ mix('js/components/file-dropzone.js') }}" defer></script>
@endonce
