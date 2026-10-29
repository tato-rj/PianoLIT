@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', ['title' => 'Shop orders', 'subtitle' => app()->environment('local') ? 'Local test orders only' : 'Payments, digital delivery and merchandise fulfillment'])
        @if(app()->environment('local'))
            <form method="POST" action="{{ route('admin.shop-orders.simulation.create') }}">@csrf<button class="btn btn-dark" type="submit">@icon('plus') Create sample order</button></form>
        @endif
    @endcomponent
    @component('admin.components.table', ['id' => 'shop-orders-table', 'label' => 'Shop orders', 'items' => $orders,
        'action' => route('admin.shop-orders.index'), 'state' => $filters,
        'search' => ['label' => 'Find an order', 'placeholder' => 'Order reference'],
        'sorts' => \App\Http\Controllers\Admin\ShopOrdersController::SORTS,
        'columns' => ['Order', 'Products', 'Total', 'Status', 'Placed'], 'emptyTitle' => 'No orders yet',
        'emptyDescription' => 'New checkout quotes and paid orders will appear here.'])
        @foreach($orders as $order)
            <tr>
                <td><a class="admin-table__primary" href="{{ route('admin.shop-orders.show', $order->id) }}">{{ $order->id }}</a><small>{{ ucfirst($order->mode) }} · {{ ucfirst($order->kind) }}</small></td>
                <td>{{ implode(', ', array_column($order->items, 'name')) }}</td>
                <td>{{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                <td>{{ $order->created_at->format('M j, Y · g:ia') }}</td>
            </tr>
        @endforeach
    @endcomponent
</div></div>
@endsection
