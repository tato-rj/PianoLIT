@extends('webapp.layouts.app', ['title' => 'Secure checkout'])

@section('content')
@include('webapp.membership.checkout.modal', ['directCheckout' => true])
<noscript><p class="p-4">Please enable JavaScript to open secure checkout. <a href="{{ route('webapp.membership.pricing') }}">Return to plans</a></p></noscript>
@endsection
