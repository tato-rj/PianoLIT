@component('components.billing.checkout.modal', [
    'cancelUrl' => route('webapp.membership.pricing'), 'directCheckout' => $directCheckout ?? false,
    'checkoutKey' => $plan->name, 'checkoutController' => mix('js/views/membership-billing.js')
])
    @slot('account')<p class="membership-account">For <strong>{{ auth('web')->user()->email }}</strong></p>@endslot
    @slot('payment')
        @if($notice = session('error'))
        <p class="alert alert-warning" role="alert">{{ $notice }}</p>
        @endif
        @include('webapp.membership.checkout.card')
    @endslot
    @slot('summary')@include('webapp.membership.checkout.summary')@endslot
@endcomponent
