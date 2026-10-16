@extends('webapp.layouts.app', ['title' => 'Secure checkout'])

@push('header')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
@include('webapp.layouts.header')
<div class="membership membership-checkout">
    @include('webapp.membership.steps', ['step' => 2])
    <header class="membership-heading membership-heading--compact">
        <h1>Your next chapter starts here.</h1>
        <p>One membership. So much music to look forward to.</p>
    </header>
    <div class="membership-checkout-grid">
        @include('webapp.membership.checkout.summary')
        <section class="membership-payment" aria-labelledby="payment-heading">
            <div class="membership-payment-heading">
                <h2 id="payment-heading">Payment details</h2>
                <span class="membership-secure">@icon('lock', ['mr' => 0]) Secure checkout</span>
            </div>
            <p class="membership-account">Subscribing as <strong>{{ auth('web')->user()->email }}</strong></p>
            @include('webapp.membership.checkout.card')
        </section>
    </div>
    <p class="membership-help">Need a hand? <a href="mailto:contact@pianolit.com">contact@pianolit.com</a></p>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/membership-billing.js') }}" defer></script>
@endpush
