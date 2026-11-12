@extends('layouts.app', ['title' => $shopTitle ?? 'PianoLIT Shop', 'projectHome' => true, 'bodyClass' => $shopBodyClass ?? 'shop-page'])

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
                    @alert(['color' => 'red', 'message' => '<strong class="me-2">Sorry | </strong>'.e($errors->getBag('storefront')->first()), 'dismissible' => true, 'floating' => 'top'])
                @endif
                @yield('shop-content')
            </div>
        @endcomponent
    </div>
    @include('components.layout.public.footer')
    @include('shop.storefront.components.alerts')
</div>
<div data-nosnippet>
    @include('shop.storefront.components.welcome-offer')
    @include('components.billing.checkout.host', ['cancelUrl' => route('shop.bag')])
</div>
@endsection

@push('scripts')
    <script src="{{ mix('js/views/storefront.js') }}" defer></script>
    <script src="{{ mix('js/views/shop-welcome.js') }}" defer></script>
@endpush
