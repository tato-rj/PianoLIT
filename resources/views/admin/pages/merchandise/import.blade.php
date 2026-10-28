@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => 'Import from Printful',
        'subtitle' => 'Choose a published product to add to your merchandise catalog.',
        'breadcrumbs' => [['label' => 'Merchandise', 'url' => route('admin.merchandise.index')]],
    ])
        <a class="btn btn-dark btn-sm" href="https://www.printful.com/dashboard/store" target="_blank" rel="noopener noreferrer">@icon('external-link')Open Printful</a>
    @endcomponent
    @if($error)
        <div class="alert alert-red" role="alert">{{ $error }}</div>
        <a href="{{ route('admin.merchandise.discover') }}" class="btn btn-dark">@icon('refresh-cw')Try again</a>
    @else
        @component('admin.components.table', [
            'id' => 'printful-products-table', 'label' => 'Printful products', 'items' => $products,
            'pageSizes' => [], 'columns' => ['Product', 'Variants', ['label' => 'Actions', 'hidden' => true]],
            'emptyTitle' => 'No products published in Printful yet',
            'emptyDescription' => 'Create your T-shirt or sticker in Printful and publish it to the PianoLIT store. A saved product template alone will not appear here.',
        ])
            @foreach($products as $remote)
            <tr>
                <td><span class="admin-table__primary">{{ $remote['name'] }}</span><small>Printful #{{ $remote['id'] }}</small></td>
                <td>{{ $remote['variants'] }}</td>
                <td>
                    @if($local = $imported->get($remote['id']))
                        @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'label' => 'Manage images', 'url' => route('admin.merchandise.edit', $local)]])
                    @else
                        <form method="POST" action="{{ route('admin.merchandise.import') }}">
                            @csrf
                            <input type="hidden" name="printful_product_id" value="{{ $remote['id'] }}">
                            @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Import product', 'icon' => 'download', 'attributes' => ['aria-label' => 'Import '.$remote['name']]]])
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        @endcomponent
        <p class="small text-muted mt-3">Importing copies the available mockups into PianoLIT. You can add your own images and choose the main image afterward.</p>
    @endif
</div></div>
@endsection
