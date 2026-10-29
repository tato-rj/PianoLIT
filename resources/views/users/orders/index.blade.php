@extends('layouts.app', ['title' => 'Track your orders'])

@section('content')
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 mb-md-5">
                <h1 class="h2 mb-0">Track your orders</h1>
                <a href="{{ route('users.purchases') }}">My downloads @icon('arrow-right')</a>
            </header>

            @if(!$orders->total())
                @include('users.orders.empty')
            @else
                @forelse($orders as $order)
                    <article class="profile-panel mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <h2 class="h5 mb-1">{{ $tracking->status($order) }}</h2>
                                <p class="text-muted mb-0"><time datetime="{{ $order->paid_at->toDateString() }}">{{ $order->paid_at->format('M j, Y') }}</time> · {{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}</p>
                            </div>
                            <a class="btn btn-secondary btn-sm" href="{{ $tracking->url($order) }}">View order @icon('arrow-right')</a>
                        </div>
                        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                            @foreach($order->items as $item)
                                <li class="d-flex align-items-center gap-3">
                                    @if(!empty($item['image']))<img class="order-history-image flex-shrink-0" src="{{ $item['image'] }}" alt="" width="64" height="64" loading="lazy">@endif
                                    <div>
                                        <p class="fw-bold mb-1">{{ $item['name'] }}</p>
                                        <p class="text-muted mb-0">{{ $item['option'] }} · Qty {{ $item['quantity'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @empty
                    <p>No orders on this page. <a href="{{ route('users.orders') }}">Back to your orders</a></p>
                @endforelse
                @if($orders->hasPages()) @pagination(['collection' => $orders]) @endif
            @endif

            <p class="text-muted text-center mt-5 mb-0">Ordered as a guest? Follow the link in your confirmation email.</p>
        </div>
    </div>
</div>
@endsection
