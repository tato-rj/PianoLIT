<div class="col-sm-6 col-xl-4">
    <article class="h-100 d-flex flex-column" aria-label="Gallery image {{ $loop->iteration }}">
        <div class="ratio ratio-1x1 mb-3"><img src="{{ $image->url }}" alt="{{ $image->alt }}" class="w-100 h-100 object-fit-contain rounded" loading="lazy"></div>
        <p class="small text-muted mb-2">{{ $image->source === 'printful' ? 'Printful mockup' : 'Your upload' }} · {{ $image->width }} × {{ $image->height }}</p>
        <form method="POST" action="{{ route('admin.merchandise.images.update', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" class="mb-3">
            @csrf @method('PATCH')
            <input type="hidden" name="revision" value="{{ $product->revision }}">
            <input type="hidden" name="editing_image_id" value="{{ $image->id }}">
            @input(['id' => 'image-description-'.$image->id, 'name' => 'alt', 'label' => 'Image description', 'limit' => 255, 'bag' => 'merchandise', 'oldInputKey' => 'description-'.$image->id, 'value' => $editingImageId === (string) $image->id ? ($draftAlt ?? $image->alt) : $image->alt])
            <button class="btn btn-secondary btn-sm" type="submit" aria-label="Save description for gallery image {{ $loop->iteration }}">Save description</button>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-auto">
            <form method="POST" action="{{ route('admin.merchandise.images.main', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
                <button class="btn btn-dark btn-sm" type="submit" @if($isMain) disabled @endif aria-label="{{ $isMain ? 'Main image' : 'Use gallery image '.$loop->iteration.' as main image' }}">@icon($isMain ? 'check' : 'star'){{ $isMain ? 'Main image' : 'Use as main' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.merchandise.images.move', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" class="d-flex gap-2">
                @csrf @method('PATCH')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
                <button class="btn btn-secondary btn-sm" type="submit" name="direction" value="up" aria-label="Move gallery image {{ $loop->iteration }} earlier" @if($first) disabled @endif>@icon('arrow-up', ['mr' => 0])</button>
                <button class="btn btn-secondary btn-sm" type="submit" name="direction" value="down" aria-label="Move gallery image {{ $loop->iteration }} later" @if($last) disabled @endif>@icon('arrow-down', ['mr' => 0])</button>
            </form>
            <form method="POST" action="{{ route('admin.merchandise.images.remove', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" onsubmit="return window.confirm('Remove this image from the gallery? You can restore it later.')">
                @csrf @method('DELETE')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Remove gallery image '.$loop->iteration]])
            </form>
        </div>
    </article>
</div>
