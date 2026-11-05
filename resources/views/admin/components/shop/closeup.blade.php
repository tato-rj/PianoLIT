@php
    $closeupPreview = $preview ?? false;
    $closeupRecord = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->for($closeupProduct);
    $closeupUrl = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->url($closeupRecord);
    $closeupVersion = $closeupRecord ? (int) $closeupRecord->revision : 0;
@endphp
@component('admin.components.editor.section', ['id' => 'hover-closeup', 'sectionId' => 'hover-closeup', 'title' => 'Hover close-up', 'class' => 'mb-4', 'preview' => $closeupPreview])
    @if($errors->getBag('closeup')->has('closeup_revision'))
        @alert(['color' => 'red', 'message' => e($errors->getBag('closeup')->first('closeup_revision'))])
    @endif
    @unless($closeupPreview)
        <form id="hover-closeup-upload" method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.store', $closeupProduct) }}" enctype="multipart/form-data">
        @csrf
    @endunless
        <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
        @if(!$closeupUrl)
            <p class="small text-muted mb-2">{{ ($closeupRecord && $closeupRecord->path) ? 'The saved close-up is unavailable. Upload a replacement or remove it.' : 'No close-up uploaded' }}</p>
        @endif
        @include('components.forms.upload.dropzone', [
            'id' => 'hover-closeup-file', 'name' => 'closeup_image',
            'label' => $closeupUrl ? 'Replace close-up' : 'Choose close-up',
            'accept' => 'image/jpeg,image/png,image/webp', 'required' => true,
            'bag' => 'closeup', 'disabled' => $closeupPreview, 'compact' => true,
            'currentUrl' => $closeupUrl, 'currentLabel' => 'Current hover close-up',
            'hint' => 'Square JPG, PNG or WebP · up to 10 MB · max 6,000px per side / 24 MP',
        ])
    @unless($closeupPreview)
        </form>
    @endunless
    <div class="d-flex align-items-center gap-2">
        <button type="{{ $closeupPreview ? 'button' : 'submit' }}" form="hover-closeup-upload" class="btn btn-primary btn-sm" @if($closeupPreview) disabled @endif>@icon('upload')Save close-up</button>
        @if($closeupRecord && $closeupRecord->path)
            <form method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.destroy', $closeupProduct) }}">
                @csrf @method('DELETE')
                <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
                <button type="submit" class="btn btn-secondary btn-sm" title="Remove close-up" aria-label="Remove close-up">@icon('trash-2', ['mr' => 0])</button>
            </form>
        @endif
    </div>
@endcomponent
