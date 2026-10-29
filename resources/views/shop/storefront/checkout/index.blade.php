@extends('layouts.app', ['title' => 'Secure checkout', 'projectHome' => true])
@section('content')
    @include('shop.storefront.checkout.modal', ['directCheckout' => true])
    <noscript><p class="p-4">Please enable JavaScript to open secure checkout. <a href="{{ route('shop.bag') }}">Return to your bag</a></p></noscript>
@endsection
