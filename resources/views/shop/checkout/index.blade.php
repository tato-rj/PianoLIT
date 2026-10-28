@extends('layouts.app', ['title' => 'Checkout | '.config('app.name')])

@php
    $productKind = $product instanceof \App\Shop\eScore ? 'eScore' : 'eBook';
    $catalogLabel = $productKind === 'eScore' ? 'eScores' : 'eBooks';
    $existingPurchase = auth()->user()->purchasesOf($product)->first();
    $membershipDownload = ! $product->isFree() && auth()->user()->isEligibleForFreeMonthlyProduct();
    $needsPayment = ! $existingPurchase && ! $product->isFree() && ! $membershipDownload;
    $totalToday = $needsPayment ? $product->finalPrice(true) : 0;
@endphp

@push('header')
@if($needsPayment)<script src="https://js.stripe.com/v3/" defer></script>@endif
@endpush

@section('content')
<section class="container shop-checkout py-4 pb-5" data-shop-checkout data-order-base="{{ $totalToday }}">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Shop', 'url' => route('shop.home')],
        ['label' => $catalogLabel, 'url' => $product->indexRoute()],
        ['label' => rtrim($product->title, '. '), 'url' => $product->showRoute()],
        ['label' => 'Checkout'],
    ]])
    <header class="mb-4 mb-lg-5">
        <h1 class="h2 mb-2">{{ $existingPurchase ? 'Your download' : 'Checkout' }}</h1>
        <p class="text-muted mb-0">{{ $existingPurchase ? 'This edition is already in your PianoLIT account.' : 'A little more music, ready for your piano.' }}</p>
    </header>
    <div class="row g-4 g-lg-5 align-items-start">
        <div class="col-lg-7 order-2 order-lg-1">
            <div class="mb-4 pb-4 border-bottom">
                <h2 class="h5 mb-2">Your account</h2>
                <p class="mb-1 text-break">{{ auth()->user()->email }}</p>
                <p class="text-muted small mb-0">Your files will be saved in My downloads.</p>
            </div>
            @if($existingPurchase)
                <h2 class="h5 mb-2">Ready when you are</h2>
                <p class="text-muted mb-3">You can download this {{ $productKind }} again at any time.</p>
                <a class="btn btn-primary" href="{{ route('shop.success', $existingPurchase) }}">@icon('file-down')Open your download</a>
            @elseif(! $needsPayment)
                <h2 class="h5 mb-2">{{ $membershipDownload ? 'Included with your membership' : 'Your free download' }}</h2>
                <p class="text-muted mb-3">{{ $membershipDownload ? 'Use your available monthly download for this edition.' : 'Add this edition to your account and choose your file on the next page.' }} No payment details needed.</p>
                <form action="{{ $product->purchaseRoute() }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">@icon('file-down')Get your download</button>
                </form>
            @else
                @component('shop.components.forms.layout')
                    @include('shop.components.forms.returning')
                @endcomponent
            @endif
            <div class="small text-muted mt-4">
                <a href="{{ $product->showRoute() }}">@icon('arrow-left')Back to product</a>
            </div>
        </div>
        <aside class="col-lg-5 order-1 order-lg-2" aria-label="Order summary">
            @include('shop.checkout.summary')
        </aside>
    </div>
</section>
@endsection

@push('scripts')
@if($needsPayment)<script src="{{ mix('js/views/checkout.js') }}" defer></script>@endif
@endpush
