<form method="POST" action="{{ route('admin.merchandise.images.upload', ['merchandise' => $product] + $returnFilters) }}" enctype="multipart/form-data" class="mt-4" data-color-upload="{{ $group['color'] }}">
    @csrf
    <input type="hidden" name="revision" value="{{ $product->revision }}">
    <input type="hidden" name="gallery_color" value="{{ $group['color'] }}">
    @include('components.forms.upload.dropzone', [
        'id' => 'color-mockups-'.$groupIndex, 'name' => 'images[]', 'label' => 'Add '.$group['label'].' mockups',
        'accept' => 'image/jpeg,image/png,image/webp', 'multiple' => true, 'required' => true, 'bag' => 'merchandise',
        'hint' => 'JPG, PNG or WebP · up to 10 images, 10 MB each',
        'showErrors' => $editingImageId === '' && $draftColor === $group['color'],
    ])
    <button class="btn btn-primary" type="submit" aria-label="Upload mockups for {{ $group['label'] }}">@icon('upload')Upload images</button>
</form>
