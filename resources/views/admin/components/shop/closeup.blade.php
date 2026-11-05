@php
    $closeupPreview = $preview ?? false;
    $closeupRecord = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->for($closeupProduct);
    $closeupUrl = $closeupPreview ? null : app(\App\Services\Shop\Closeups::class)->url($closeupRecord);
    $closeupVersion = $closeupRecord ? (int) $closeupRecord->revision : 0;
@endphp
@component('admin.components.editor.section', ['id' => 'hover-closeup', 'sectionId' => 'hover-closeup', 'title' => 'Hover close-up', 'class' => 'mb-4', 'preview' => $closeupPreview,
    'description' => 'Upload a finished, square detail image for the product card hover view. The image appears as uploaded.'])
    @if($errors->getBag('closeup')->has('closeup_revision'))
        @alert(['color' => 'red', 'message' => e($errors->getBag('closeup')->first('closeup_revision'))])
    @endif
    @if($closeupUrl)
        <img src="{{ $closeupUrl }}" alt="Current hover close-up" class="img-fluid border rounded mb-3" width="240" height="240" style="max-height:240px;object-fit:contain">
    @elseif($closeupRecord && $closeupRecord->path)
        <p class="text-muted">The saved close-up is unavailable. Upload a replacement or remove it.</p>
    @else
        <p class="text-muted">No close-up uploaded</p>
    @endif
    @unless($closeupPreview)
        <form method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.store', $closeupProduct) }}" enctype="multipart/form-data">
        @csrf
    @endunless
        <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
        @include('components.forms.upload.dropzone', ['id' => 'hover-closeup-file', 'name' => 'closeup_image', 'label' => $closeupUrl ? 'Replace close-up' : 'Choose close-up', 'accept' => 'image/jpeg,image/png,image/webp', 'required' => true, 'bag' => 'closeup', 'disabled' => $closeupPreview])
        <p class="small text-muted">Maximum 6,000 pixels per side and 24 megapixels. Use a square image to avoid cropping.</p>
        <button type="{{ $closeupPreview ? 'button' : 'submit' }}" class="btn btn-primary" @if($closeupPreview) disabled @endif>@icon('upload')Save close-up</button>
    @unless($closeupPreview)
        </form>
    @endunless
    @if($closeupRecord && $closeupRecord->path)
        <form method="POST" action="{{ route('admin.'.$closeupKind.'.closeup.destroy', $closeupProduct) }}" class="mt-3">
            @csrf @method('DELETE')
            <input type="hidden" name="closeup_revision" value="{{ $closeupVersion }}">
            <button type="submit" class="btn btn-secondary">@icon('x')Remove close-up</button>
        </form>
    @endif
@endcomponent
