@php
    $mockupPreview = $mockupPreview ?? false;
    $viewNumber = $viewNumber ?? $loop->iteration;
    $rowLabel = ($image->gallery_color ?? 'Unassigned').' view '.$viewNumber;
    $canAssign = $canAssign ?? false;
@endphp
<div class="col-12">
    <article class="d-flex align-items-center gap-2 py-2 border-bottom" aria-label="{{ $rowLabel }}" data-mockup-row @if($mockupPreview) data-ds-preview @endif>
        <img src="{{ $image->url }}" alt="{{ app(\App\Services\Shop\MerchandiseGallery::class)->description($product, $image->gallery_color, $viewNumber) }}" width="48" height="48" class="object-fit-contain rounded flex-shrink-0" loading="lazy">
        @if($canAssign)
            <form method="POST" action="{{ route('admin.merchandise.images.update', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" class="d-flex align-items-end gap-2">
                @csrf @method('PATCH')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
                <input type="hidden" name="editing_image_id" value="{{ $image->id }}">
                @select(['id' => 'image-color-'.$image->id, 'name' => 'gallery_color', 'label' => 'Color', 'bag' => 'merchandise', 'useOldInput' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-0', 'value' => $editingImageId === (string) $image->id ? $draftColor : '', 'choices' => ['' => 'Choose color'] + array_combine($galleryColors, $galleryColors)])
                @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'size' => 'xs', 'label' => 'Assign color', 'icon' => 'check', 'iconOnly' => true]])
            </form>
        @else
            <span class="small text-muted">View {{ $viewNumber }}</span>
        @endif
        <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
            @unless($mockupPreview)
            <form method="POST" action="{{ route('admin.merchandise.images.move', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
            @endunless
                <div class="d-flex gap-1">
                    @include('admin.components.table-action', ['tableAction' => ['type' => $mockupPreview ? 'button' : 'submit', 'size' => 'xs', 'label' => 'Move '.$rowLabel.' earlier', 'icon' => 'arrow-up', 'iconOnly' => true, 'attributes' => ['name' => 'direction', 'value' => 'up', 'disabled' => $first || $mockupPreview]]])
                    @include('admin.components.table-action', ['tableAction' => ['type' => $mockupPreview ? 'button' : 'submit', 'size' => 'xs', 'label' => 'Move '.$rowLabel.' later', 'icon' => 'arrow-down', 'iconOnly' => true, 'attributes' => ['name' => 'direction', 'value' => 'down', 'disabled' => $last || $mockupPreview]]])
                </div>
            @unless($mockupPreview)</form>@endunless
            @unless($mockupPreview)
            <form method="POST" action="{{ route('admin.merchandise.images.main', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
            @endunless
                @include('admin.components.table-action', ['tableAction' => ['type' => $mockupPreview ? 'button' : 'submit', 'size' => 'xs', 'tone' => 'dark', 'label' => $isMain ? 'Main image: '.$rowLabel : 'Use '.$rowLabel.' as main image', 'icon' => $isMain ? 'check' : 'star', 'iconOnly' => true, 'attributes' => ['disabled' => $isMain || $mockupPreview]]])
            @unless($mockupPreview)</form>@endunless
            @unless($mockupPreview)
            <form method="POST" action="{{ route('admin.merchandise.images.remove', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" onsubmit="return window.confirm('Remove this mockup? You can restore it later.')">
                @csrf @method('DELETE')
                <input type="hidden" name="revision" value="{{ $product->revision }}">
            @endunless
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => $mockupPreview ? 'button' : 'submit', 'size' => 'xs', 'label' => 'Remove '.$rowLabel, 'attributes' => ['disabled' => $mockupPreview]]])
            @unless($mockupPreview)</form>@endunless
        </div>
    </article>
</div>
