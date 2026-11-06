@php
    $digitalHover = in_array($closeupKind ?? '', ['ebooks', 'escores'], true);
    $hoverLabel = $digitalHover ? 'hover image' : 'close-up';
    $closeupPreview = $preview ?? false;
    $closeupRecord = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->for($closeupProduct);
    $closeupUrl = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->url($closeupRecord);
    $closeupVersion = $closeupRecord ? (int) $closeupRecord->revision : 0;
    $hasCloseup = $closeupRecord && !empty($closeupRecord->path);
@endphp
@component('admin.components.editor.section', ['id' => 'hover-closeup', 'sectionId' => 'hover-closeup', 'title' => $digitalHover ? 'Hover image' : 'Hover close-up', 'class' => 'mb-4', 'preview' => $closeupPreview])
    @if($digitalHover)<p class="small text-muted">Optional artwork shown when a customer hovers over the catalog image, such as an open book or a styled product view.</p>@endif
    @if($hasCloseup)
        @slot('actions')
            <form method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.destroy', $closeupProduct) }}">
                @csrf @method('DELETE')
                <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
                <button type="submit" class="btn btn-red btn-sm" title="Remove {{ $hoverLabel }}" aria-label="Remove {{ $hoverLabel }}">@icon('trash-2', ['mr' => 0])</button>
            </form>
        @endslot
    @endif
    @if($errors->getBag('closeup')->has('closeup_revision'))
        @alert(['color' => 'red', 'message' => e($errors->getBag('closeup')->first('closeup_revision'))])
    @endif
    @if($hasCloseup)
        @if($closeupUrl)
            <img src="{{ $closeupUrl }}" alt="{{ $digitalHover ? 'Current hover image' : 'Current hover close-up' }}" width="{{ $closeupRecord->width }}" height="{{ $closeupRecord->height }}" class="d-block img-fluid w-100 rounded">
        @else
            <p class="small text-muted mb-0">The saved {{ $hoverLabel }} is unavailable. Remove it to upload another image.</p>
        @endif
    @else
        <p class="small text-muted mb-2">No {{ $hoverLabel }} uploaded</p>
        @unless($closeupPreview)
            <form id="hover-closeup-upload" method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.store', $closeupProduct) }}" enctype="multipart/form-data">
            @csrf
        @endunless
        <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
        @include('components.forms.upload.dropzone', [
            'id' => 'hover-closeup-file', 'name' => 'closeup_image',
            'label' => 'Choose '.$hoverLabel,
            'accept' => 'image/jpeg,image/png,image/webp', 'required' => true,
            'bag' => 'closeup', 'disabled' => $closeupPreview, 'compact' => true,
            'hint' => 'Square JPG, PNG or WebP · up to 10 MB · max 6,000px per side / 24 MP',
        ])
        <button type="{{ $closeupPreview ? 'button' : 'submit' }}" class="btn btn-primary btn-sm" @if($closeupPreview) disabled @endif>@icon('upload')Save {{ $hoverLabel }}</button>
        @unless($closeupPreview)
            </form>
        @endunless
    @endif
@endcomponent
