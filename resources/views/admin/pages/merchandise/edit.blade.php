@extends('admin.layouts.app')

@section('content')
@php
    $editingImageId = is_scalar(old('editing_image_id')) ? (string) old('editing_image_id') : '';
    $draftAlt = is_string(old('alt')) ? old('alt') : null;
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Manage product images', 'subtitle' => $product->name,
        'breadcrumbs' => [['label' => 'Merchandise', 'url' => route('admin.merchandise.index', $returnFilters)]],
    ])
        <form method="POST" action="{{ route('admin.merchandise.refresh', ['merchandise' => $product] + $returnFilters) }}">
            @csrf
            <input type="hidden" name="revision" value="{{ $product->revision }}">
            <button class="btn btn-dark btn-sm" type="submit">@icon('refresh-cw')Refresh from Printful</button>
        </form>
    @endcomponent
    @if($errors->getBag('merchandise')->any())
        <div class="alert alert-red" role="alert"><ul class="mb-0">@foreach($errors->getBag('merchandise')->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @if(session('merchandise_notice'))<div class="alert alert-grey" role="status">{{ session('merchandise_notice') }}</div>@endif
    <div class="row g-4 mb-4">
        <div class="col-lg-8 order-2 order-lg-1" id="images">
            @component('admin.components.editor.section', ['id' => 'merchandise-gallery', 'title' => 'Product gallery', 'description' => 'Choose the main image and arrange the other views. Refreshing preserves your uploads and main-image choice.'])
                @if($product->images->isEmpty())
                    <p class="text-muted mb-0">No images yet. Upload your own mockups, or refresh after Printful finishes generating its previews.</p>
                @else
                    <div class="row g-4">
                        @foreach($product->images as $image)
                            @include('admin.pages.merchandise.image', ['isMain' => (int) $product->main_image_id === $image->id, 'first' => $loop->first, 'last' => $loop->last])
                        @endforeach
                    </div>
                @endif
            @endcomponent
            @if($removed->isNotEmpty())
                <details class="admin-editor-section mt-4">
                    <summary class="fw-semibold">Removed images ({{ $removed->count() }})</summary>
                    <p class="small text-muted mt-3">These images stay out of the gallery when you refresh. Restore any image you want to use again.</p>
                    <div class="row g-3">
                        @foreach($removed as $image)
                        <div class="col-sm-6 col-xl-4">
                            <img src="{{ $image->url }}" alt="{{ $image->alt }}" width="96" height="96" class="object-fit-contain rounded d-block mb-2" loading="lazy">
                            <form method="POST" action="{{ route('admin.merchandise.images.restore', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}">
                                @csrf
                                <input type="hidden" name="revision" value="{{ $product->revision }}">
                                <button class="btn btn-dark btn-sm" type="submit" aria-label="Restore {{ $image->alt }}">@icon('rotate-ccw')Restore image</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
        <div class="col-lg-4 order-1 order-lg-2">
            @component('admin.components.editor.section', ['id' => 'merchandise-upload', 'title' => 'Upload your own images', 'description' => 'Add your own mockups, product photos or additional Printful views.'])
                <form method="POST" action="{{ route('admin.merchandise.images.upload', ['merchandise' => $product] + $returnFilters) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="revision" value="{{ $product->revision }}">
                    @include('components.forms.upload.file', ['id' => 'merchandise-images', 'name' => 'images[]', 'label' => 'Choose images', 'accept' => 'image/jpeg,image/png,image/webp', 'multiple' => true, 'required' => true, 'bag' => 'merchandise'])
                    @input(['id' => 'upload-image-alt', 'name' => 'alt', 'label' => 'Image description', 'required' => '', 'limit' => 255, 'placeholder' => 'For example: black T-shirt, front view', 'bag' => 'merchandise', 'oldInputKey' => 'upload-alt', 'value' => $editingImageId === '' ? ($draftAlt ?? '') : ''])
                    <p class="small text-muted">JPG, PNG or WebP · up to 10 files per upload, 10 MB each. Maximum 6,000 pixels per side and 24 megapixels. Transparent backgrounds are preserved. Leave the description blank to use the product name.</p>
                    <button class="btn btn-primary" type="submit">@icon('upload')Upload images</button>
                </form>
            @endcomponent
            <p class="small text-muted mt-3 mb-0">Printful #{{ $product->printful_product_id }} · {{ count($product->variants) }} variants<br>Last refreshed {{ optional($product->synced_at)->format('M j, Y · g:ia') ?: 'Not recorded' }}</p>
        </div>
    </div>
    <div id="variants" class="mb-4">
        @component('admin.components.table', [
            'id' => 'merchandise-variants', 'label' => 'Product variants', 'title' => 'Product variants', 'items' => $variants, 'pageSizes' => [],
            'description' => 'Sizes, colors and retail prices imported from Printful.',
            'columns' => ['Variant', 'Size', 'Color', 'Retail price', 'Printful status'],
            'emptyTitle' => 'No variants available', 'emptyDescription' => 'Finish publishing the product variants in Printful, then refresh this product.',
        ])
            @foreach($variants as $variant)
            <tr>
                <td>{{ $variant['name'] ?: 'Variant #'.$variant['id'] }}<small>Printful #{{ $variant['id'] }}@if($variant['sku']) · SKU {{ $variant['sku'] }}@endif</small></td>
                <td>{{ $variant['size'] ?: '—' }}</td><td>{{ $variant['color'] ?: '—' }}</td>
                <td>{{ $variant['retail_price'] !== null ? $variant['retail_price'].' '.$variant['currency'] : 'Not set' }}</td>
                <td>{{ $variant['discontinued'] ? 'Discontinued' : ($variant['out_of_stock'] ? 'Out of stock' : ($variant['synced'] ? 'Synced' : 'Not synced')) }}</td>
            </tr>
            @endforeach
        @endcomponent
    </div>
</div></div>
@endsection
