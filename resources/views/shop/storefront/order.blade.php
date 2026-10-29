@extends('shop.storefront.layout', ['shopTitle' => 'Your order | PianoLIT Shop'])
@section('shop-content')
    @include('components.navigation.breadcrumb', ['items' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Shop', 'url' => route('shop.home')], ['label' => 'Your order']]])
    <h1 class="mb-4">{{ !empty($privateTracking) ? 'Track your order' : ($order->paid_at ? 'Thank you for your order' : 'Checking your payment') }}</h1>
    @if($order->mode !== 'live')<p class="mb-4"><strong>Test order</strong> — {{ $order->kind === 'digital' ? 'this purchase belongs to your local test account.' : 'no real Printful order or shipment has been created.' }}</p>@endif
    @if($order->status === 'review_required')
        <p>Your order needs a review. Please <a href="mailto:contact@pianolit.com">contact us</a> with the order reference below.</p>
    @elseif($order->mode === 'live' && $order->submitted_at && empty($privateTracking))
        <p>Your payment is confirmed and your order has been sent for printing and delivery.</p>
    @elseif($order->kind === 'digital' && $order->purchase_id)
        <p>Your payment is confirmed and your download is ready.</p>
        <a class="shop-button shop-button--dark mb-4" href="{{ route('shop.success', $order->purchase_id) }}">Open your download @icon('file-down')</a>
    @elseif($order->paid_at && empty($privateTracking))
        <p>{{ $order->mode === 'live' ? 'Your payment is confirmed. Your order is being prepared for fulfillment.' : 'Your test checkout is complete.' }}</p>
    @elseif(!$order->paid_at)
        <p>Your payment has not been confirmed yet. Refresh this page to check its status before making another payment.</p>
    @endif
    <p>Order reference: {{ $order->id }}</p>
    @if($order->kind === 'merchandise' && $order->paid_at)
        @include('shop.storefront.components.order-tracking')
    @endif
    <div class="row g-4 my-4">
        <div class="col-12 col-md-7">
            @foreach($order->items as $item)
                <div class="mb-4"><h2>{{ $item['name'] }}</h2><p>{{ $item['option'] }} · Quantity {{ $item['quantity'] }}</p></div>
            @endforeach
        </div>
        <div class="col-12 col-md-5">
            <h2>Order total</h2>
            <p>{{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}</p>
            <p>{{ $order->shipping_name }}</p>
        </div>
    </div>
    <a class="shop-button shop-button--mint" href="{{ route('shop.home') }}">Continue shopping @icon('arrow-right')</a>
@endsection
