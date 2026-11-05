@php
    $closeupPreview = $preview ?? false;
    $closeupRecord = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->for($closeupProduct);
    $closeupUrl = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->url($closeupRecord);
    $closeupVersion = $closeupRecord ? (int) $closeupRecord->revision : 0;
    $hasCloseup = $closeupRecord && !empty($closeupRecord->path);
@endphp
@component('admin.components.editor.section', ['id' => 'hover-closeup', 'sectionId' => 'hover-closeup', 'title' => 'Hover close-up', 'class' => 'mb-4', 'preview' => $closeupPreview])
    @if($hasCloseup)
        @slot('actions')
            <form method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.destroy', $closeupProduct) }}">
                @csrf @method('DELETE')
                <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
                <button type="submit" class="btn btn-red btn-sm" title="Remove close-up" aria-label="Remove close-up">@icon('trash-2', ['mr' => 0])</button>
            </form>
        @endslot
    @endif
    @if($errors->getBag('closeup')->has('closeup_revision'))
        @alert(['color' => 'red', 'message' => e($errors->getBag('closeup')->first('closeup_revision'))])
    @endif
    @if($hasCloseup)
        @if($closeupUrl)
            <img src="{{ $closeupUrl }}" alt="Current hover close-up" width="{{ $closeupRecord->width }}" height="{{ $closeupRecord->height }}" class="d-block img-fluid w-100 rounded">
        @else
            <p class="small text-muted mb-0">The saved close-up is unavailable. Remove it to upload another image.</p>
        @endif
    @else
        <p class="small text-muted mb-2">No close-up uploaded</p>
        @unless($closeupPreview)
            <form id="hover-closeup-upload" method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.store', $closeupProduct) }}" enctype="multipart/form-data">
            @csrf
        @endunless
        <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
        @include('components.forms.upload.dropzone', [
            'id' => 'hover-closeup-file', 'name' => 'closeup_image',
            'label' => 'Choose close-up',
            'accept' => 'image/jpeg,image/png,image/webp', 'required' => true,
            'bag' => 'closeup', 'disabled' => $closeupPreview, 'compact' => true,
            'hint' => 'Square JPG, PNG or WebP · up to 10 MB · max 6,000px per side / 24 MP',
        ])
        <button type="{{ $closeupPreview ? 'button' : 'submit' }}" class="btn btn-primary btn-sm" @if($closeupPreview) disabled @endif>@icon('upload')Save close-up</button>
        @unless($closeupPreview)
            </form>
        @endunless
    @endif
@endcomponent
