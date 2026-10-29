@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Merchandise',
        'subtitle' => app()->environment('local') ? 'Local sample products. Printful is disconnected on this machine.' : 'Import Printful products and manage the images for your shop.',
        'action' => app(\App\Services\Shop\Printful::class)->enabled() ? ['label' => 'Import from Printful', 'url' => route('admin.merchandise.discover')] : null,
    ])
    @if($syncNotice)<div class="alert alert-grey" role="status">{{ $syncNotice }}</div>@endif
    @component('admin.components.table', [
        'id' => 'merchandise-table', 'label' => 'Merchandise', 'items' => $products,
        'action' => route('admin.merchandise.index'), 'state' => $filters,
        'search' => ['label' => 'Find a product', 'placeholder' => 'Product name'],
        'sorts' => \App\Http\Controllers\Admin\MerchandiseController::SORTS,
        'columns' => [
            ['label' => 'Product', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            'Variants', 'Images', 'Last refreshed', ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => $filters['search'] !== '' ? 'No matching products' : 'No merchandise yet',
        'emptyDescription' => $filters['search'] !== '' ? 'Try another product name or clear the search.' : 'Publish a product to your PianoLIT store in Printful, then import it here.',
        'emptyAction' => app(\App\Services\Shop\Printful::class)->enabled() ? ['label' => 'Import from Printful', 'url' => route('admin.merchandise.discover')] : null,
    ])
        @foreach($products as $product)
        <tr>
            <td><div class="d-flex align-items-center gap-3">
                @if($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="" width="64" height="64" class="rounded object-fit-contain" loading="lazy">@endif
                <div><a class="admin-table__primary" href="{{ route('admin.merchandise.edit', ['merchandise' => $product] + $returnFilters) }}">{{ $product->name }}</a><small>{{ (int) $product->printful_store_id === 0 ? 'Local sample' : 'Printful #'.$product->printful_product_id }}</small></div>
            </div></td>
            <td>{{ count($product->variants) }}</td>
            <td>{{ $product->images_count }}</td>
            <td>{{ optional($product->synced_at)->format('M j, Y · g:ia') ?: 'Not refreshed' }}</td>
            <td>@include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'label' => 'Manage images', 'url' => route('admin.merchandise.edit', ['merchandise' => $product] + $returnFilters)]])</td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@endsection
