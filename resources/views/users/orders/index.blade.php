@extends('layouts.app', ['title' => 'Track your orders'])

@section('content')
<div class="container py-4 py-md-5">
    <header class="mb-4 mb-md-5">
        <h1 class="mb-2">Track your orders</h1>
        <p class="text-muted mb-3">Your merchandise orders, shipping updates and tracking links.</p>
        <a href="{{ route('users.purchases') }}">Looking for an eBook or eScore? Open My downloads @icon('arrow-right')</a>
    </header>
    @forelse($orders as $order)
        <article class="profile-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div><h2 class="h4 mb-2">{{ $tracking->status($order) }}</h2><p class="text-muted mb-0">{{ $order->paid_at->format('F j, Y') }} · {{ app(\App\Services\Shop\Storefront::class)->money($order->total, strtoupper($order->currency)) }}</p></div>
                <a class="btn btn-secondary" href="{{ $tracking->url($order) }}">View order @icon('arrow-right')</a>
            </div>
            @foreach($order->items as $item)<p class="mb-2">{{ $item['name'] }} · {{ $item['option'] }} × {{ $item['quantity'] }}</p>@endforeach
            <p class="text-muted mb-0 text-break">Order reference: {{ $order->id }}</p>
        </article>
    @empty
        <p>You don’t have any merchandise orders in this account yet. If you checked out as a guest, use the private tracking link in your confirmation email.</p>
        <a class="btn btn-primary mt-3" href="{{ route('shop.home') }}">Visit the Shop @icon('arrow-right')</a>
    @endforelse
    @if($orders->hasPages()) @pagination(['collection' => $orders]) @endif
</div>
@endsection
