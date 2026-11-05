@extends('admin.layouts.app')

@section('content')
@php
    $editingImageId = is_scalar(old('editing_image_id')) ? (string) old('editing_image_id') : '';
    $draftColor = is_string(old('gallery_color')) ? old('gallery_color') : '';
    $colorGroups = array_values(array_filter($galleryGroups, fn ($group) => $group['color'] !== ''));
    $gallery = app(\App\Services\Shop\MerchandiseGallery::class);
    $colorCodes = \App\Services\Shop\MerchandiseColors::options($product->variants ?? []);
    $colorTabs = array_map(fn ($group) => [
        'target' => $gallery->tabId($group['color']), 'label' => $group['label'].' ('.$group['images']->count().')',
        'swatch' => $colorCodes[$group['color']]['color_code'] ?? null,
        'swatchSecondary' => $colorCodes[$group['color']]['color_code2'] ?? null,
    ], $colorGroups);
    $activeColorGroup = collect($colorGroups)->firstWhere('color', $draftColor);
    $activeColorTab = $activeColorGroup ? $gallery->tabId($activeColorGroup['color']) : ($colorTabs[0]['target'] ?? null);
    $printfulMockups = $product->images->where('source', 'printful');
    $unassignedUploads = $product->images->where('source', 'upload')->whereNull('gallery_color');
    $removed = $removed->where('source', 'upload');
@endphp
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Manage product images', 'subtitle' => $product->name,
        'breadcrumbs' => [['label' => 'Merchandise', 'url' => route('admin.merchandise.index', $returnFilters)]],
    ])@endcomponent
    @if($errors->getBag('merchandise')->any())
        <div class="alert alert-red" role="alert"><ul class="mb-0">@foreach($errors->getBag('merchandise')->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @if(session('merchandise_notice'))<div class="alert alert-grey" role="status">{{ session('merchandise_notice') }}</div>@endif
    <div id="images">
        <div class="row g-4 mb-4 align-items-start">
            <div class="col-lg-8">
                @component('admin.components.editor.section', ['id' => 'merchandise-gallery', 'title' => 'Mockups by color', 'description' => 'Upload the views for each product color and arrange their order. The storefront shows only the views for the selected color.'])
                    @if($colorGroups)
                    @component('components.navigation.tab-navigation', ['id' => 'merchandise-color-tabs', 'label' => 'Product colors', 'items' => $colorTabs, 'active' => $activeColorTab, 'tone' => 'neutral', 'class' => 'text-nowrap'])
                        <div class="tab-content pt-3 mb-3">
                        @foreach($colorGroups as $groupIndex => $group)
                        @php($colorTabId = $gallery->tabId($group['color']))
                        <section class="tab-pane {{ $activeColorTab === $colorTabId ? 'show active' : '' }}" id="{{ $colorTabId }}" role="tabpanel" aria-labelledby="merchandise-color-tabs-{{ $colorTabId }}" tabindex="0">
                            @if(!$group['offered'])<p class="small text-muted">This color is no longer offered. Reassign its images to an available color to use them.</p>@endif
                            <div class="row g-0">
                                @forelse($group['images'] as $image)
                                    @include('admin.pages.merchandise.image', ['isMain' => (int) $product->main_image_id === $image->id, 'first' => $loop->first, 'last' => $loop->last, 'viewNumber' => $loop->iteration, 'canAssign' => !$group['offered']])
                                @empty
                                    <div class="col-12"><p class="text-muted mb-0">No mockups uploaded for this color.</p></div>
                                @endforelse
                            </div>
                            @if($group['offered'])@include('admin.pages.merchandise.upload')@endif
                        </section>
                        @endforeach
                        </div>
                    @endcomponent
                    @else
                        <p class="text-muted mb-0">No product colors available. Add variants in Printful, then refresh the product details.</p>
                    @endif
                    <p class="small text-muted mb-0">The star selects the catalog image. Maximum 40 images per product, 6,000 pixels per side and 24 megapixels.</p>
                @endcomponent
            </div>
            <div class="col-lg-4">
                @include('admin.components.shop.closeup', ['closeupProduct' => $product, 'closeupKind' => 'merchandise'])
            </div>
        </div>
        <details class="admin-editor-section mb-4" id="printful-mockups" @if($printfulMockups->contains('id', (int) $editingImageId)) open @endif>
            <summary class="fw-semibold">Printful mockups ({{ $printfulMockups->count() }})</summary>
            <p class="small text-muted mt-3">Download originals as starting images for your own mockups. Upload finished images in the color galleries above. Manage originals in Printful.</p>
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                @if(app(\App\Services\Shop\Printful::class)->enabled() && (int) $product->printful_store_id !== 0)
                    <form method="POST" action="{{ route('admin.merchandise.refresh', ['merchandise' => $product] + $returnFilters) }}">
                        @csrf
                        <input type="hidden" name="revision" value="{{ $product->revision }}">
                        <button class="btn btn-secondary btn-sm" type="submit">@icon('refresh-cw')Refresh from Printful</button>
                    </form>
                @endif
                <p class="small text-muted mb-0">Printful #{{ $product->printful_product_id }} · Last refreshed {{ optional($product->synced_at)->format('M j, Y · g:ia') ?: 'Not recorded' }}</p>
            </div>
            <div class="row g-4">
                @forelse($printfulMockups as $image)
                    @include('admin.pages.merchandise.source-image')
                @empty
                    <div class="col-12"><p class="text-muted mb-0">No mockups imported from Printful.</p></div>
                @endforelse
            </div>
        </details>
        @if($unassignedUploads->isNotEmpty())
            <details class="admin-editor-section mb-4" @if($unassignedUploads->contains('id', (int) $editingImageId)) open @endif>
                <summary class="fw-semibold">Unassigned uploads ({{ $unassignedUploads->count() }})</summary>
                <p class="text-muted mt-3">These earlier uploads have no color assigned. Choose a color to add each image to its gallery.</p>
                <div class="row g-0">
                    @foreach($unassignedUploads as $image)
                        @include('admin.pages.merchandise.image', ['isMain' => (int) $product->main_image_id === $image->id, 'first' => $loop->first, 'last' => $loop->last, 'viewNumber' => $loop->iteration, 'canAssign' => true])
                    @endforeach
                </div>
            </details>
        @endif
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
    <details id="variants" class="admin-editor-section mt-4 mb-4">
        <summary class="fw-semibold mb-3">Product variants ({{ count($product->variants) }})</summary>
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
    </details>
</div></div>
@endsection
