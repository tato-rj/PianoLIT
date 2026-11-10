@component('admin.components.editor.section', ['id' => 'infographic-artwork', 'title' => 'Image'])
    @if($infograph && $infograph->cover_path)
    <div class="mb-3">
        <a href="{{ storage($infograph->cover_path) }}" target="_blank" rel="noopener noreferrer" aria-label="Open current infographic image"><img src="{{ storage($infograph->cover_path) }}" alt="{{ $infograph->name }}" class="w-100 rounded"></a>
        <p class="small text-muted mt-2 mb-0">{{ number_format($infograph->width) }} × {{ number_format($infograph->height) }} px</p>
    </div>
    @endif
    @include('components.forms.upload.dropzone', [
        'bag' => 'infographic', 'id' => 'infographic-image', 'name' => 'cover_image',
        'label' => $infograph ? 'Replace image (optional)' : 'Upload image', 'accept' => 'image/jpeg', 'required' => !$infograph,
        'compact' => true, 'hint' => $infograph ? 'JPG · up to 10 MB. Leave empty to keep the current image.' : 'JPG · up to 10 MB',
    ])
@endcomponent
