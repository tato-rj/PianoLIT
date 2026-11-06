@component('admin.components.editor.section', ['id' => 'digital-gallery', 'sectionId' => 'display-images', 'title' => 'Display images', 'description' => 'Upload product views and arrange their order. The star selects the main catalog image.'])
    @forelse($displayImages as $image)
        @include('admin.pages.digital.image', ['viewNumber' => $loop->iteration, 'first' => $loop->first, 'last' => $loop->last])
    @empty
        <p class="text-muted">No display images uploaded.</p>
    @endforelse
    <form method="POST" action="{{ route('admin.'.$kind.'.images.upload', $product) }}" enctype="multipart/form-data" class="mt-3">
        @csrf
        <input type="hidden" name="media_revision" value="{{ $mediaState['revision'] }}">
        @include('components.forms.upload.dropzone', [
            'id' => 'digital-images', 'name' => 'images[]', 'label' => 'Add display images',
            'accept' => 'image/jpeg,image/png,image/webp', 'multiple' => true, 'required' => true,
            'hint' => 'JPG, PNG or WebP · up to 10 images, 10 MB each',
        ])
        <button class="btn btn-primary btn-sm" type="submit">@icon('upload')Upload images</button>
    </form>
    <p class="small text-muted mt-3 mb-0">Maximum 40 images per product, 6,000 pixels per side and 24 megapixels</p>
    @if($removedImages->isNotEmpty())
        <details class="border-top mt-3 pt-3">
            <summary class="fw-semibold">Removed images ({{ $removedImages->count() }})</summary>
            @foreach($removedImages as $image)
                <div class="d-flex align-items-center gap-2 mt-3">
                    <img src="{{ asset('storage/'.$image['path']) }}" alt="{{ $product->title }} removed image {{ $loop->iteration }}" width="48" height="48" class="object-fit-contain rounded" loading="lazy">
                    <form method="POST" action="{{ route('admin.'.$kind.'.images.action', [$product, $image['id']]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="media_revision" value="{{ $mediaState['revision'] }}">
                        <button type="submit" name="action" value="restore" class="btn btn-secondary btn-sm">@icon('rotate-ccw')Restore image</button>
                    </form>
                </div>
            @endforeach
        </details>
    @endif
@endcomponent
