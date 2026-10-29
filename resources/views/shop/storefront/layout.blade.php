@extends('layouts.app', ['title' => $shopTitle ?? 'PianoLIT Shop', 'projectHome' => true, 'bodyClass' => 'shop-page'])

@push('header')
    <link rel="stylesheet" href="{{ mix('css/storefront.css') }}">
@endpush

@section('content')
<div class="shop-storefront d-flex flex-column min-vh-100" data-storefront>
    @include('shop.storefront.components.header')
    <div class="shop-content flex-grow-1">
        @component('components.layout.public.content')
            <div class="container py-4 pb-5">
                @if($errors->getBag('storefront')->any())
                    <div class="shop-form-error mb-4" role="alert">{{ $errors->getBag('storefront')->first() }}</div>
                @endif
                @yield('shop-content')
            </div>
        @endcomponent
    </div>
    @include('components.layout.public.footer')
    <div class="shop-toast" role="status" aria-live="polite" hidden data-shop-notice>
        <span data-shop-notice-text></span>
        <a href="{{ route('shop.bag') }}">View bag @icon('arrow-right')</a>
    </div>
</div>
@include('components.billing.checkout.host', ['cancelUrl' => route('shop.bag')])
@endsection

@push('scripts')
    <script src="{{ mix('js/views/storefront.js') }}" defer></script>
@endpush
