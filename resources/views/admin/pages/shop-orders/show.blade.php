@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', ['title' => 'Order details', 'subtitle' => $order->id,
        'breadcrumbs' => [['label' => 'Shop orders', 'url' => route('admin.shop-orders.index')]]])
        @if($order->kind === 'merchandise' && $order->paid_at && $order->status === 'paid')
            <form action="{{ route('admin.shop-orders.retry', $order->id) }}" method="POST">@csrf<button type="submit" class="btn btn-dark">@icon('refresh-cw') Retry fulfillment</button></form>
        @endif
        @if($order->mode === 'live' && $order->printful_order_id)<a class="btn btn-secondary" href="https://www.printful.com/dashboard/default/orders/{{ $order->printful_order_id }}" target="_blank" rel="noopener noreferrer">Open Printful @icon('external-link')</a>@endif
    @endcomponent
    @if($canSimulate)
        @include('admin.pages.shop-orders.simulation')
    @endif
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            @component('admin.components.table', ['id' => 'order-items-table', 'label' => 'Order items', 'items' => collect($order->items),
                'columns' => ['Product', 'Option', 'Quantity', 'Price']])
                @foreach($order->items as $item)<tr><td>{{ $item['name'] }}</td><td>{{ $item['option'] }}</td><td>{{ $item['quantity'] }}</td><td>{{ app(\App\Services\Shop\Storefront::class)->money($item['unit_price'] * $item['quantity'], strtoupper($order->currency)) }}</td></tr>@endforeach
            @endcomponent
        </div>
        <div class="col-12 col-lg-5">
            <h2 class="h4">Payment and fulfillment</h2>
            <p>{{ ucfirst($order->mode) }} · {{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
            <p>Total: {{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}</p>
            @if($order->last_error)<p>Review: {{ str_replace('_', ' ', $order->last_error) }}</p>@endif
            <h2 class="h4 mt-4">{{ $order->kind === 'digital' ? 'Billing details' : 'Delivery details' }}</h2>
            <p>{{ $order->recipient['name'] }}<br>{{ $order->recipient['email'] }}</p>
            <p>{{ $order->recipient['address1'] }}<br>@if(!empty($order->recipient['address2'])){{ $order->recipient['address2'] }}<br>@endif{{ $order->recipient['city'] }}, {{ $order->recipient['state_code'] ?? '' }} {{ $order->recipient['zip'] ?? '' }}<br>{{ $order->recipient['country_code'] }}</p>
        </div>
    </div>
    @if($order->kind === 'merchandise')
        <section class="mt-5">
            <h2 class="h4">Shipment updates</h2>
            <p>{{ app(\App\Services\Shop\OrderTracking::class)->status($order) }}@if($order->tracking_checked_at) · Last checked {{ $order->tracking_checked_at->format('M j, Y · g:ia') }}@endif</p>
            @if($order->tracking_error)<p>Last check: {{ $order->tracking_error }}</p>@endif
            @foreach($order->shipments as $shipment)
                <p>{{ $shipment->carrier }} · {{ $shipment->tracking_number }} · {{ ucfirst($shipment->status) }}
                    @if($shipment->tracking_url)<a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener noreferrer">Track package @icon('external-link')</a>@endif
                </p>
            @endforeach
        </section>
        <section class="mt-5">
            <h2 class="h4">Customer emails</h2>
            @forelse($order->emails as $email)
                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    <p class="mb-0">{{ \App\Services\Shop\OrderEmails::TYPES[$email->kind]['label'] ?? ucfirst($email->kind) }} · {{ ucfirst($email->status) }}@if($email->sent_at) · {{ $email->sent_at->format('M j, Y · g:ia') }}@endif</p>
                    @if($canSimulate && $email->status === 'preview')
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.shop-orders.simulation.email', [$order->id, $email->id, 'html']) }}" target="_blank" rel="noopener noreferrer">View email @icon('external-link')</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.shop-orders.simulation.email', [$order->id, $email->id, 'text']) }}" target="_blank" rel="noopener noreferrer">Plain text @icon('external-link')</a>
                    @endif
                    @if($email->status === 'uncertain')
                        <form method="POST" action="{{ route('admin.shop-orders.email.retry', [$order->id, $email->id]) }}" onsubmit="return window.confirm('Retry only after checking that the email provider did not deliver this message. Send it again?')">
                            @csrf<button class="btn btn-secondary btn-sm" type="submit">Retry email</button>
                        </form>
                    @endif
                </div>
            @empty<p>No customer emails queued.</p>@endforelse
        </section>
    @endif
</div></div>
@endsection
